<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

/**
 * Dashboard aggregate model.
 */
final class DashboardModel extends BaseDatabaseModel
{
	/**
	 * Returns dashboard metrics and scheduler information.
	 *
	 * @return array<string,mixed>
	 */
	public function getData(): array
	{
		$tasks = [
			'queue' => $this->getSchedulerTask('pungamail.process_queue'),
			'scheduled' => $this->getSchedulerTask('pungamail.scheduled_sends'),
			'digests' => $this->getSchedulerTask('pungamail.generate_digests'),
			'bounces' => $this->getSchedulerTask('pungamail.process_bounces'),
		];

		return [
			'version' => $this->getVersion(),
			'subscribers' => $this->getSubscriberCounts(),
			'queue' => $this->getQueueCounts(),
			'newsletters' => $this->getNewsletterCounts(),
			'task' => $tasks['queue'],
			'tasks' => $tasks,
			'automation' => $this->getAutomationNeeds(),
		];
	}

	/** @return string */
	private function getVersion(): string
	{
		$db = $this->getDatabase();
		$type = 'component';
		$element = 'com_pungamail';
		$query = $db->getQuery(true)
			->select($db->quoteName('manifest_cache'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = :type')
			->where($db->quoteName('element') . ' = :element')
			->bind(':type', $type)
			->bind(':element', $element);
		$manifest = json_decode((string) $db->setQuery($query)->loadResult(), true);

		return is_array($manifest) && isset($manifest['version']) ? (string) $manifest['version'] : '0.3.2';
	}

	/** @return array<string,int> */
	private function getSubscriberCounts(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'COUNT(*) AS total',
				'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending',
				'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS subscribed',
				'SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS unsubscribed',
			])
			->from($db->quoteName('#__pungamail_subscribers'));
		$row = $db->setQuery($query)->loadAssoc() ?: [];
		$suppressions = (int) $db->setQuery(
			$db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__pungamail_suppressions'))
		)->loadResult();

		return [
			'total' => (int) ($row['total'] ?? 0),
			'pending' => (int) ($row['pending'] ?? 0),
			'subscribed' => (int) ($row['subscribed'] ?? 0),
			'unsubscribed' => (int) ($row['unsubscribed'] ?? 0),
			'suppressed' => $suppressions,
		];
	}

	/** @return array<string,int> */
	private function getQueueCounts(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				"SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending",
				"SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing",
				"SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent",
				"SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed",
			])
			->from($db->quoteName('#__pungamail_send_queue'));
		$row = $db->setQuery($query)->loadAssoc() ?: [];

		return [
			'pending' => (int) ($row['pending'] ?? 0),
			'processing' => (int) ($row['processing'] ?? 0),
			'sent' => (int) ($row['sent'] ?? 0),
			'failed' => (int) ($row['failed'] ?? 0),
		];
	}

	/** @return array<string,int> */
	private function getNewsletterCounts(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'SUM(CASE WHEN state <> -2 THEN 1 ELSE 0 END) AS active',
				'SUM(CASE WHEN state = -2 THEN 1 ELSE 0 END) AS trashed',
				'SUM(CASE WHEN state <> -2 AND status = 0 THEN 1 ELSE 0 END) AS drafts',
				'SUM(CASE WHEN state <> -2 AND status IN (3, 4) THEN 1 ELSE 0 END) AS sent',
			])
			->from($db->quoteName('#__pungamail_newsletters'));
		$row = $db->setQuery($query)->loadAssoc() ?: [];

		return [
			'active' => (int) ($row['active'] ?? 0),
			'trashed' => (int) ($row['trashed'] ?? 0),
			'drafts' => (int) ($row['drafts'] ?? 0),
			'sent' => (int) ($row['sent'] ?? 0),
		];
	}

	/**
	 * Returns the configured Joomla Scheduled Task for a Punga Mail routine.
	 *
	 * @param string $type Joomla scheduler task type.
	 *
	 * @return object|null Task row, or null when no task has been configured.
	 */
	private function getSchedulerTask(string $type): ?object
	{
		$db = $this->getDatabase();

		try
		{
			$query = $db->getQuery(true)
				->select([
					$db->quoteName('id'),
					$db->quoteName('title'),
					$db->quoteName('state'),
					$db->quoteName('last_execution'),
					$db->quoteName('next_execution'),
					$db->quoteName('last_exit_code'),
				])
				->from($db->quoteName('#__scheduler_tasks'))
				->where($db->quoteName('type') . ' = :type')
				->order($db->quoteName('state') . ' DESC')
				->order($db->quoteName('id') . ' ASC')
				->bind(':type', $type)
				->setLimit(1);

			return $db->setQuery($query)->loadObject() ?: null;
		}
		catch (\Throwable)
		{
			return null;
		}
	}

	/**
	 * Returns counts that determine which optional task warnings are relevant.
	 *
	 * @return array{digests:int,scheduled:int,bounce_configured:bool}
	 */
	private function getAutomationNeeds(): array
	{
		$db = $this->getDatabase();
		$digestState = 1;
		$scheduledStatus = 5;
		$digests = 0;
		$scheduled = 0;
		$mailboxHost = '';

		try
		{
			$digests = (int) $db->setQuery(
				$db->getQuery(true)
					->select('COUNT(*)')
					->from($db->quoteName('#__pungamail_digests'))
					->where($db->quoteName('state') . ' = :digestState')
					->bind(':digestState', $digestState, ParameterType::INTEGER)
			)->loadResult();
		}
		catch (\Throwable)
		{
			// Keep the Dashboard usable if a site has not completed its schema update.
		}

		try
		{
			$scheduled = (int) $db->setQuery(
				$db->getQuery(true)
					->select('COUNT(*)')
					->from($db->quoteName('#__pungamail_newsletters'))
					->where($db->quoteName('state') . ' <> -2')
					->where($db->quoteName('status') . ' = :scheduledStatus')
					->bind(':scheduledStatus', $scheduledStatus, ParameterType::INTEGER)
			)->loadResult();
		}
		catch (\Throwable)
		{
			// The primary Dashboard metrics remain more useful than a fatal page.
		}

		try
		{
			$mailboxId = 1;
			$mailboxHost = (string) $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('bounce_host'))
					->from($db->quoteName('#__pungamail_mail_settings'))
					->where($db->quoteName('id') . ' = :mailboxId')
					->bind(':mailboxId', $mailboxId, ParameterType::INTEGER)
			)->loadResult();
		}
		catch (\Throwable)
		{
			// Mailbox warnings are optional when the 0.3 schema is unavailable.
		}

		return [
			'digests' => $digests,
			'scheduled' => $scheduled,
			'bounce_configured' => trim($mailboxHost) !== '',
		];
	}
}
