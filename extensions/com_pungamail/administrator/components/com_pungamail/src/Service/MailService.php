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
use Joomla\Registry\Registry;

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
		private readonly DatabaseInterface $db,
		private readonly MailConfigurationService $mailConfiguration,
		private readonly MailSettingsRepository $mailSettings
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
		$newsletterId = (int) $newsletter->id;
		$unsubscribeUrl = $this->unsubscribeUrl((int) $recipient->subscriber_id, $newsletterId);
		$html = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8'), $personalized['html']);
		$text = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, $unsubscribeUrl, $personalized['text']);
		$browserUrl = (int) ($newsletter->snapshot_browser_enabled ?? 0) === 1
			? $this->browserUrl($newsletterId, (string) ($newsletter->snapshot_browser_token ?? ''))
			: '#';
		$html = str_replace(NewsletterRenderer::BROWSER_PLACEHOLDER, htmlspecialchars($browserUrl, ENT_QUOTES, 'UTF-8'), $html);
		$text = str_replace(NewsletterRenderer::BROWSER_PLACEHOLDER, $browserUrl, $text);
		$oneClickUrl = $this->oneClickUnsubscribeUrl((int) $recipient->subscriber_id, $newsletterId);
		$headers = ['List-Unsubscribe' => '<' . $oneClickUrl . '>'];

		if (trim((string) ($newsletter->snapshot_list_id ?? '')) !== '')
		{
			$headers['List-ID'] = (string) $newsletter->snapshot_list_id;
		}

		$headers['X-PungaMail-Newsletter-ID'] = (string) $newsletterId;
		$headers['X-PungaMail-Queue-ID'] = (string) $recipient->id;

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
			$headers,
			(string) ($newsletter->snapshot_reply_to_email ?? ''),
			(string) ($newsletter->snapshot_reply_to_name ?? ''),
			$this->mailConfiguration->envelopeSender((int) $recipient->id)
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
	public function sendTest(string $email, string $subject, string $html, string $text, string $replyToEmail = '', string $replyToName = ''): void
	{
		$html = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, '#', $html);
		$text = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, '[' . Text::_('COM_PUNGAMAIL_TEST_UNSUBSCRIBE_DISABLED') . ']', $text);
		$html = str_replace(NewsletterRenderer::BROWSER_PLACEHOLDER, '#', $html);
		$text = str_replace(NewsletterRenderer::BROWSER_PLACEHOLDER, '[' . Text::_('COM_PUNGAMAIL_TEST_BROWSER_DISABLED') . ']', $text);
		$this->sendMultipart($email, '[TEST] ' . $subject, $html, $text, [], $replyToEmail, $replyToName);
	}

	/** Sends a small diagnostic through the currently active Punga Mail transport. */
	public function sendConfigurationTest(string $email): void
	{
		$this->sendConfigurationTestWithOutgoing($email, null);
	}

	/**
	 * Tests posted outgoing settings before they are saved.
	 *
	 * @param string              $email       Test recipient.
	 * @param array<string,mixed> $settings    Posted outgoing settings.
	 * @param string              $newPassword Newly entered SMTP password.
	 * @return void
	 */
	public function sendConfigurationTestUsingSettings(string $email, array $settings, string $newPassword): void
	{
		$outgoing = $this->mailSettings->outgoingFromInput($settings, $newPassword);
		$this->sendConfigurationTestWithOutgoing($email, $outgoing);
	}

	/** @param object|null $outgoing Optional outgoing settings override. @return void */
	private function sendConfigurationTestWithOutgoing(string $email, ?object $outgoing): void
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_TEST_EMAIL'));
		}

		$siteName = (string) Factory::getApplication()->get('sitename');
		$subject = Text::sprintf('COM_PUNGAMAIL_MAIL_TEST_SUBJECT', $siteName);
		$text = Text::sprintf('COM_PUNGAMAIL_MAIL_TEST_BODY', $siteName);
		$html = '<!doctype html><html><body><p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
		$this->sendMultipart($email, $subject, $html, $text, [], '', '', '', $outgoing);
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
		$this->sendConfirmationLink($email, $link);
	}

	/** Sends a confirmation for an email-only subscriber's topic changes. */
	public function sendPreferenceConfirmation(string $email, string $token): void
	{
		$link = $this->siteLink('index.php?option=com_pungamail&view=confirm&kind=topics&token=' . rawurlencode($token));
		$this->sendConfirmationLink($email, $link);
	}

	/** Renders the configurable confirmation message for a supplied safe link. */
	private function sendConfirmationLink(string $email, string $link): void
	{
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
	 * Sends a notification that an Automatic Newsletter created a draft for review.
	 *
	 * @param string $email           Reviewer address.
	 * @param string $automaticTitle Automatic Newsletter title.
	 * @param string $draftTitle     Generated Newsletter title.
	 * @param int    $itemCount      Included content items.
	 * @param int    $blockedCount   Items excluded by Joomla access permissions.
	 * @param string $reviewUrl      Absolute administrator review URL.
	 *
	 * @return void
	 */
	public function sendAutomaticDraftNotification(
		string $email,
		string $automaticTitle,
		string $draftTitle,
		int $itemCount,
		int $blockedCount,
		string $reviewUrl
	): void
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_AUTOMATIC_DRAFT_EMAIL'));
		}

		$language = Factory::getApplication()->getLanguage();
		$language->load('com_pungamail', JPATH_ADMINISTRATOR . '/components/com_pungamail', null, true);
		$subject = Text::sprintf('COM_PUNGAMAIL_AUTOMATIC_DRAFT_NOTIFICATION_SUBJECT', $automaticTitle);
		$markdown = Text::sprintf(
			'COM_PUNGAMAIL_AUTOMATIC_DRAFT_NOTIFICATION_BODY',
			$automaticTitle,
			$draftTitle,
			$itemCount,
			$blockedCount,
			$reviewUrl
		);
		$htmlBody = $this->markdown->toHtml($markdown, Uri::root());
		$textBody = $this->markdown->toText($markdown, Uri::root());
		$html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>';
		$html .= '<body style="font-family:Arial,Helvetica,sans-serif;color:#222;line-height:1.55">' . $htmlBody . '</body></html>';
		$this->sendMultipart($email, $subject, $html, $textBody);
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
	public function unsubscribeUrl(int $subscriberId, int $newsletterId = 0): string
	{
		$token = $this->tokens->createUnsubscribeToken($subscriberId);
		$link = 'index.php?option=com_pungamail&view=unsubscribe&id=' . $subscriberId . '&token=' . rawurlencode($token);

		if ($newsletterId > 0)
		{
			$link .= '&mid=' . $newsletterId;
		}

		return $this->siteLink($link);
	}

	/**
	 * Returns the RFC 8058 one-click POST endpoint.
	 *
	 * @param int $subscriberId Subscriber ID.
	 *
	 * @return string Absolute URL.
	 */
	public function oneClickUnsubscribeUrl(int $subscriberId, int $newsletterId = 0): string
	{
		$token = $this->tokens->createUnsubscribeToken($subscriberId);
		$link = 'index.php?option=com_pungamail&task=subscription.oneClickUnsubscribe&id=' . $subscriberId . '&token=' . rawurlencode($token);

		if ($newsletterId > 0)
		{
			$link .= '&mid=' . $newsletterId;
		}

		return $this->siteLink($link);
	}

	/** @return string */
	public function browserUrl(int $newsletterId, string $token): string
	{
		return $this->siteLink('index.php?option=com_pungamail&view=browser&id=' . $newsletterId . '&key=' . rawurlencode($token));
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
	private function sendMultipart(
		string $recipient,
		string $subject,
		string $html,
		string $text,
		array $headers = [],
		string $replyToEmail = '',
		string $replyToName = '',
		string $envelopeSender = '',
		?object $outgoing = null
	): void
	{
		$params = ComponentHelper::getParams('com_pungamail');
		$mailer = $this->createMailer($outgoing);
		$fromEmail = trim((string) $params->get('from_email'));
		$fromName = trim((string) $params->get('from_name'));

		if (!$mailer instanceof Mail)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_MAILER_TYPE'));
		}

		if (!filter_var($recipient, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_TEST_EMAIL'));
		}

		if ($fromEmail !== '')
		{
			if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL))
			{
				throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_FROM_INVALID'));
			}

			// Joomla's Mail API accepts a [mail, name] sender tuple. Apply the
			// Punga Mail sender consistently to either supported transport mode.
			$mailer->setSender([$fromEmail, $this->headerValue($fromName)]);
		}

		$mailer->addRecipient($recipient);

		if ($replyToEmail !== '')
		{
			if (!filter_var($replyToEmail, FILTER_VALIDATE_EMAIL))
			{
				throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_REPLY_TO_INVALID'));
			}

			$mailer->addReplyTo($replyToEmail, $this->headerValue($replyToName));
		}

		if ($envelopeSender !== '' && property_exists($mailer, 'Sender'))
		{
			$mailer->Sender = $envelopeSender;
		}
		$mailer->setSubject($this->headerValue($subject));
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

		$result = $mailer->send();

		if ($result !== true)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_MAIL_SEND_FAILED'));
		}
	}

	/**
	 * Creates the active Joomla or Punga Mail-specific mailer.
	 *
	 * @param object|null $override Optional validated outgoing settings for a test message.
	 * @return Mail Configured Joomla mail object.
	 */
	private function createMailer(?object $override = null): Mail
	{
		$outgoing = $override ?? $this->mailSettings->getOutgoingConnection();

		if ((string) ($outgoing->smtp_mode ?? 'joomla') !== 'custom')
		{
			$mailer = $this->mailerFactory->createMailer();
		}
		else
		{
			$sender = $this->mailConfiguration->sender();
			$settings = new Registry([
				'mailer' => 'smtp',
				'smtpauth' => (int) ($outgoing->smtp_auth ?? 0),
				'smtpuser' => (string) ($outgoing->smtp_username ?? ''),
				'smtppass' => (string) ($outgoing->smtp_password ?? ''),
				'smtphost' => (string) ($outgoing->smtp_host ?? ''),
				'smtpsecure' => (string) ($outgoing->smtp_security ?? 'tls'),
				'smtpport' => (int) ($outgoing->smtp_port ?? 587),
				'mailfrom' => (string) $sender['email'],
				'fromname' => (string) $sender['name'],
			]);
			$mailer = $this->mailerFactory->createMailer($settings);
		}

		if (!$mailer instanceof Mail)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_MAILER_TYPE'));
		}

		return $mailer;
	}

	/**
	 * Removes control characters from values passed to mail headers.
	 *
	 * @param string $value Header value.
	 *
	 * @return string Single-line header value.
	 */
	private function headerValue(string $value): string
	{
		$value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';

		return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
	}
}
