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
		return [
			'version' => $this->getVersion(),
			'subscribers' => $this->getSubscriberCounts(),
			'queue' => $this->getQueueCounts(),
			'newsletters' => $this->getNewsletterCounts(),
			'task' => $this->getSchedulerTask(),
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

		return is_array($manifest) && isset($manifest['version']) ? (string) $manifest['version'] : '0.2.5';
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
	 * Returns the configured Joomla Scheduled Task for the Punga Mail worker.
	 *
	 * @return object|null Task row, or null when no task has been configured.
	 */
	private function getSchedulerTask(): ?object
	{
		$db = $this->getDatabase();
		$type = 'pungamail.process_queue';

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
}
