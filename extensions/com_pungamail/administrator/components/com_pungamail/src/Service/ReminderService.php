<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Implements the optional newsletter-age reminder cycle.
 */
final class ReminderService
{
	/** @param DatabaseInterface $db Database connection. @param MailService $mail Mail service. */
	public function __construct(private readonly DatabaseInterface $db, private readonly MailService $mail)
	{
	}

	/**
	 * Checks the last completed newsletter and sends at most one reminder for it.
	 *
	 * @return array{sent:bool,reason:string}
	 */
	public function process(): array
	{
		$params = ComponentHelper::getParams('com_pungamail');

		if ((int) $params->get('reminder_enabled', 0) !== 1)
		{
			return ['sent' => false, 'reason' => 'disabled'];
		}

		$email = trim((string) $params->get('reminder_email', ''));

		if ($email === '')
		{
			$email = (string) Factory::getApplication()->get('mailfrom', '');
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return ['sent' => false, 'reason' => 'invalid_recipient'];
		}

		$sentStatus = NewsletterRepository::STATUS_SENT;
		$failureStatus = NewsletterRepository::STATUS_SENT_WITH_FAILURES;
		$query = $this->db->getQuery(true)
			->select(['id', 'title', 'sent_at', 'reminder_sent_at'])
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->whereIn($this->db->quoteName('status'), [$sentStatus, $failureStatus])
			->where($this->db->quoteName('sent_at') . ' IS NOT NULL')
			->order($this->db->quoteName('sent_at') . ' DESC');
		$newsletter = $this->db->setQuery($query, 0, 1)->loadObject();

		if ($newsletter === null || $newsletter->reminder_sent_at !== null)
		{
			return ['sent' => false, 'reason' => $newsletter === null ? 'no_newsletter' : 'already_sent'];
		}

		$days = max(1, (int) $params->get('reminder_days', 30));
		$sent = new Date((string) $newsletter->sent_at, 'UTC');
		$now = new Date('now', 'UTC');
		$ageDays = (int) floor(($now->toUnix() - $sent->toUnix()) / 86400);

		if ($ageDays < $days)
		{
			return ['sent' => false, 'reason' => 'not_due'];
		}

		$siteName = (string) Factory::getApplication()->get('sitename');
		$subject = trim((string) $params->get('reminder_subject', '')) ?: Text::_('COM_PUNGAMAIL_REMINDER_DEFAULT_SUBJECT');
		$body = trim((string) $params->get('reminder_markdown', '')) ?: Text::_('COM_PUNGAMAIL_REMINDER_DEFAULT_BODY');
		$replace = [
			'{days}' => (string) $ageDays,
			'{last_newsletter}' => (string) $newsletter->title,
			'{last_sent_date}' => (string) $newsletter->sent_at,
			'{site_name}' => $siteName,
		];
		$this->mail->sendReminder($email, strtr($subject, $replace), strtr($body, $replace));
		$reminderAt = $now->toSql();
		$id = (int) $newsletter->id;
		$update = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('reminder_sent_at') . ' = :reminderAt')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':reminderAt', $reminderAt)
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($update)->execute();

		return ['sent' => true, 'reason' => 'sent'];
	}
}
