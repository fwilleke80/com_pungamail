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

/**
 * Creates all outgoing Punga Mail messages through Joomla's configured mailer.
 */
final class MailService
{
	/**
	 * @param MailerFactoryInterface $mailerFactory Joomla mailer factory.
	 * @param TokenService           $tokens        Token service.
	 * @param MarkdownRenderer       $markdown      Safe Markdown renderer.
	 */
	public function __construct(
		private readonly MailerFactoryInterface $mailerFactory,
		private readonly TokenService $tokens,
		private readonly MarkdownRenderer $markdown
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
		$unsubscribeUrl = $this->unsubscribeUrl((int) $recipient->subscriber_id);
		$html = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8'), (string) $newsletter->snapshot_html);
		$text = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, $unsubscribeUrl, (string) $newsletter->snapshot_text);
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
			(string) $newsletter->snapshot_subject,
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
		$link = Route::link(
			'site',
			'index.php?option=com_pungamail&view=confirm&token=' . rawurlencode($token),
			false,
			Route::TLS_IGNORE,
			true
		);
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

		return Route::link('site', $link, false, Route::TLS_IGNORE, true);
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
