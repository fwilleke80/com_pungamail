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
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Router\Route;

/**
 * Creates all outgoing Punga Mail messages through Joomla's configured mailer.
 */
final class MailService
{
	/**
	 * @param MailerFactoryInterface $mailerFactory Joomla mailer factory.
	 * @param TokenService           $tokens        Token service.
	 */
	public function __construct(
		private readonly MailerFactoryInterface $mailerFactory,
		private readonly TokenService $tokens
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
		$text = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, '[disabled in test message]', $text);
		$this->sendMultipart($email, '[TEST] ' . $subject, $html, $text);
	}

	/**
	 * Sends a double-opt-in confirmation message.
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
		$siteName = (string) Factory::getApplication()->get('sitename');
		$subject = 'Confirm your newsletter subscription';
		$html = '<p>Please confirm that you want to receive the ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' newsletter.</p>';
		$html .= '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Confirm subscription</a></p>';
		$html .= '<p>If you did not request this, you can ignore this message.</p>';
		$text = "Please confirm that you want to receive the {$siteName} newsletter.\n\n{$link}\n\nIf you did not request this, you can ignore this message.";
		$this->sendMultipart($email, $subject, $html, $text);
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
	 * Sends a multipart/alternative message through Joomla.
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
			throw new \RuntimeException('Punga Mail 0.1 requires Joomla\\CMS\\Mail\\Mail to create HTML multipart messages.');
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
