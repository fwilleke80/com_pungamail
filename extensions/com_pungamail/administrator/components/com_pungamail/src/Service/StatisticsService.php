<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Computes operational transport statistics from immutable queue history. */
final class StatisticsService
{
	/** @param DatabaseInterface $db Database connection. */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/** @return array<string,int> */
	public function forNewsletter(object $newsletter): array
	{
		$newsletterId = (int) $newsletter->id;
		$query = $this->db->getQuery(true)
			->select([
				'COUNT(*) AS queued',
				"SUM(CASE WHEN status IN ('sent', 'bounced') THEN 1 ELSE 0 END) AS transport_accepted",
				"SUM(CASE WHEN status = 'pending' AND attempts > 0 THEN 1 ELSE 0 END) AS temporary_failures",
				"SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS permanent_failures",
				"SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) AS remaining",
				"SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled",
			])
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$queue = $this->db->setQuery($query)->loadObject();
		$hard = 'hard';
		$soft = 'soft';
		$bounceQuery = $this->db->getQuery(true)
			->select([
				'SUM(CASE WHEN classification = :hard THEN 1 ELSE 0 END) AS hard_bounces',
				'SUM(CASE WHEN classification = :soft THEN 1 ELSE 0 END) AS soft_bounces',
			])
			->from($this->db->quoteName('#__pungamail_bounces'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':hard', $hard)
			->bind(':soft', $soft)
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$bounces = $this->db->setQuery($bounceQuery)->loadObject();
		$eventType = 'unsubscribe_completed';
		$oneClick = 'one_click_unsubscribe';
		$unsubscribeQuery = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__pungamail_events'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->whereIn($this->db->quoteName('event_type'), [$eventType, $oneClick])
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return [
			'intended' => (int) ($newsletter->intended_count ?? $newsletter->recipient_count ?? 0),
			'queued' => (int) ($queue->queued ?? 0),
			'transport_accepted' => (int) ($queue->transport_accepted ?? 0),
			'temporary_failures' => (int) ($queue->temporary_failures ?? 0),
			'permanent_failures' => (int) ($queue->permanent_failures ?? 0),
			'hard_bounces' => (int) ($bounces->hard_bounces ?? 0),
			'soft_bounces' => (int) ($bounces->soft_bounces ?? 0),
			'suppressed' => (int) ($newsletter->suppressed_count ?? 0),
			'unsubscribes' => (int) $this->db->setQuery($unsubscribeQuery)->loadResult(),
			'remaining' => (int) ($queue->remaining ?? 0),
			'cancelled' => (int) ($queue->cancelled ?? 0),
		];
	}
}
