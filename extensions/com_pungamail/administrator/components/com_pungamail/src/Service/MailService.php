<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Creates all outgoing Punga Mail messages through Joomla's configured mailer.
 */
final class MailService
{
	/**
	 * @param MailerFactoryInterface $mailerFactory Joomla mailer factory.
	 * @param TokenService           $tokens        Token service.
	 * @param MarkdownRenderer       $markdown      Safe Markdown renderer.
	 * @param NewsletterRenderer     $renderer      Newsletter personalization service.
	 */
	public function __construct(
		private readonly MailerFactoryInterface $mailerFactory,
		private readonly TokenService $tokens,
		private readonly MarkdownRenderer $markdown,
		private readonly NewsletterRenderer $renderer,
		private readonly DatabaseInterface $db
	)
	{
	}

	/**
	 * Sends a frozen newsletter to one queue recipient.
	 *
	 * @param object $newsletter Newsletter snapshot row.
	 * @param object $recipient  Queue row.
	 *
	 * @return void
	 */
	public function sendNewsletter(object $newsletter, object $recipient): void
	{
		$recipientName = trim((string) ($recipient->recipient_name ?? ''));

		if ($recipientName === '')
		{
			$recipientName = (string) $recipient->email;
		}

		$personalized = $this->renderer->personalize(
			(string) $newsletter->snapshot_subject,
			(string) $newsletter->snapshot_html,
			(string) $newsletter->snapshot_text,
			$recipientName
		);
		$unsubscribeUrl = $this->unsubscribeUrl((int) $recipient->subscriber_id);
		$html = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8'), $personalized['html']);
		$text = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, $unsubscribeUrl, $personalized['text']);
		$oneClickUrl = $this->oneClickUnsubscribeUrl((int) $recipient->subscriber_id);
		$headers = ['List-Unsubscribe' => '<' . $oneClickUrl . '>'];

		// RFC 8058 requires an HTTPS URI for one-click unsubscribe. Keep the
		// ordinary List-Unsubscribe header on development HTTP sites, but do
		// not advertise RFC 8058 POST semantics unless the endpoint is HTTPS.
		if (str_starts_with(strtolower($oneClickUrl), 'https://'))
		{
			$headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
		}

		$this->sendMultipart(
			(string) $recipient->email,
			$personalized['subject'],
			$html,
			$text,
			$headers
		);
	}

	/**
	 * Sends a preview/test newsletter without a functional unsubscribe action.
	 *
	 * @param string $email   Test recipient.
	 * @param string $subject Subject.
	 * @param string $html    Rendered HTML.
	 * @param string $text    Rendered plain text.
	 *
	 * @return void
	 */
	public function sendTest(string $email, string $subject, string $html, string $text): void
	{
		$html = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, '#', $html);
		$text = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, '[' . Text::_('COM_PUNGAMAIL_TEST_UNSUBSCRIBE_DISABLED') . ']', $text);
		$this->sendMultipart($email, '[TEST] ' . $subject, $html, $text);
	}

	/**
	 * Sends the editor-configurable double-opt-in confirmation message.
	 *
	 * Supported placeholders are `{confirmation_url}`, `{site_name}` and
	 * `{email}`. The same Markdown source is rendered to HTML and plain text.
	 *
	 * @param string $email Email address.
	 * @param string $token Raw confirmation token.
	 *
	 * @return void
	 */
	public function sendConfirmation(string $email, string $token): void
	{
		$link = $this->siteLink('index.php?option=com_pungamail&view=confirm&token=' . rawurlencode($token));
		$params = ComponentHelper::getParams('com_pungamail');
		$siteName = (string) Factory::getApplication()->get('sitename');
		$subjectTemplate = trim((string) $params->get('confirmation_subject', ''));
		$bodyTemplate = trim((string) $params->get('confirmation_markdown', ''));

		if ($subjectTemplate === '')
		{
			$subjectTemplate = Text::_('COM_PUNGAMAIL_CONFIRMATION_DEFAULT_SUBJECT');
		}

		if ($bodyTemplate === '')
		{
			$bodyTemplate = Text::_('COM_PUNGAMAIL_CONFIRMATION_DEFAULT_INTRO')
				. "\n\n[" . Text::_('COM_PUNGAMAIL_CONFIRMATION_DEFAULT_LINK') . ']({confirmation_url})'
				. "\n\n" . Text::_('COM_PUNGAMAIL_CONFIRMATION_DEFAULT_IGNORE');
		}

		$replacements = [
			'{confirmation_url}' => $link,
			'{site_name}' => $siteName,
			'{email}' => $email,
		];
		$subject = str_replace(["\r", "\n"], ' ', strtr($subjectTemplate, $replacements));
		$bodyMarkdown = strtr($bodyTemplate, $replacements);
		$bodyHtml = $this->markdown->toHtml($bodyMarkdown, Uri::root());
		$bodyText = $this->markdown->toText($bodyMarkdown, Uri::root());
		$html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>';
		$html .= '<body style="font-family:Arial,Helvetica,sans-serif;color:#222;line-height:1.55">' . $bodyHtml . '</body></html>';
		$this->sendMultipart($email, $subject, $html, $bodyText);
	}

	/**
	 * Sends a Markdown reminder message.
	 *
	 * @param string $email    Recipient address.
	 * @param string $subject  Subject.
	 * @param string $markdown Markdown body.
	 *
	 * @return void
	 */
	public function sendReminder(string $email, string $subject, string $markdown): void
	{
		$htmlBody = $this->markdown->toHtml($markdown, Uri::root());
		$textBody = $this->markdown->toText($markdown, Uri::root());
		$html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>';
		$html .= '<body style="font-family:Arial,Helvetica,sans-serif;color:#222;line-height:1.55">' . $htmlBody . '</body></html>';
		$this->sendMultipart($email, $subject, $html, $textBody);
	}

	/**
	 * Returns the visible unsubscribe page URL.
	 *
	 * @param int $subscriberId Subscriber ID.
	 *
	 * @return string Absolute URL.
	 */
	public function unsubscribeUrl(int $subscriberId): string
	{
		$token = $this->tokens->createUnsubscribeToken($subscriberId);
		$link = 'index.php?option=com_pungamail&view=unsubscribe&id=' . $subscriberId . '&token=' . rawurlencode($token);

		return $this->siteLink($link);
	}

	/**
	 * Returns the RFC 8058 one-click POST endpoint.
	 *
	 * @param int $subscriberId Subscriber ID.
	 *
	 * @return string Absolute URL.
	 */
	public function oneClickUnsubscribeUrl(int $subscriberId): string
	{
		$token = $this->tokens->createUnsubscribeToken($subscriberId);
		$link = 'index.php?option=com_pungamail&task=subscription.oneClickUnsubscribe&id=' . $subscriberId . '&token=' . rawurlencode($token);

		return $this->siteLink($link);
	}

	/**
	 * Builds an absolute site URL anchored to the published Punga Mail
	 * subscription menu item when one is available.
	 *
	 * A published hidden menu item is sufficient; this preserves Joomla's
	 * normal SEF routing without requiring the menu item to be visible.
	 *
	 * @param string $link Non-SEF site route.
	 *
	 * @return string Absolute routed URL.
	 */
	private function siteLink(string $link): string
	{
		$clientId = 0;
		$published = 1;
		$componentPattern = '%option=com_pungamail%';
		$viewPattern = '%view=subscription%';
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('client_id') . ' = :clientId')
			->where($this->db->quoteName('published') . ' = :published')
			->where($this->db->quoteName('link') . ' LIKE :componentPattern')
			->where($this->db->quoteName('link') . ' LIKE :viewPattern')
			->order($this->db->quoteName('id') . ' ASC')
			->bind(':clientId', $clientId, ParameterType::INTEGER)
			->bind(':published', $published, ParameterType::INTEGER)
			->bind(':componentPattern', $componentPattern)
			->bind(':viewPattern', $viewPattern);
		$itemId = (int) $this->db->setQuery($query, 0, 1)->loadResult();

		if ($itemId > 0 && !str_contains($link, 'Itemid='))
		{
			$link .= '&Itemid=' . $itemId;
		}

		return Route::link('site', $link, false, Route::TLS_IGNORE, true);
	}

	/**
	 * Sends a multipart/alternative message through Joomla's configured mailer.
	 *
	 * @param string               $recipient Recipient email.
	 * @param string               $subject   Subject.
	 * @param string               $html      HTML body.
	 * @param string               $text      Plain-text body.
	 * @param array<string,string> $headers   Optional custom headers.
	 *
	 * @return void
	 */
	private function sendMultipart(string $recipient, string $subject, string $html, string $text, array $headers = []): void
	{
		$params = ComponentHelper::getParams('com_pungamail');
		$mailer = $this->mailerFactory->createMailer();
		$fromEmail = trim((string) $params->get('from_email'));
		$fromName = trim((string) $params->get('from_name'));

		if ($fromEmail !== '')
		{
			$mailer->setSender($fromEmail, $fromName);
		}

		if (!$mailer instanceof Mail)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_MAILER_TYPE'));
		}

		$mailer->addRecipient($recipient);
		$mailer->setSubject($subject);
		$mailer->isHtml(true);
		$mailer->setBody($html);

		// Joomla's default Mail implementation is PHPMailer based and exposes
		// AltBody for multipart/alternative messages.
		$mailer->AltBody = $text;

		foreach ($headers as $name => $value)
		{
			if (method_exists($mailer, 'addCustomHeader'))
			{
				$mailer->addCustomHeader($name, $value);
			}
		}

		$mailer->send();
	}
}
