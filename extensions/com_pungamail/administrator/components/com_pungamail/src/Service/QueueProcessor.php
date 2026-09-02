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
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Processes the persistent send queue safely across retries and workers.
 */
final class QueueProcessor
{
	/**
	 * @param DatabaseInterface    $db          Database connection.
	 * @param NewsletterRepository $newsletters Newsletter repository.
	 * @param MailService          $mail        Mail service.
	 */
	public function __construct(
		private readonly DatabaseInterface $db,
		private readonly NewsletterRepository $newsletters,
		private readonly MailService $mail
	)
	{
	}

	/**
	 * Processes one configurable queue batch.
	 *
	 * @param int|null $batchSize Optional batch-size override.
	 *
	 * @return array{processed:int,sent:int,failed:int,retried:int}
	 */
	public function process(?int $batchSize = null): array
	{
		$params = ComponentHelper::getParams('com_pungamail');

		if ((int) $params->get('queue_paused', 0) === 1)
		{
			return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'retried' => 0, 'paused' => 1];
		}
		$batchSize ??= (int) $params->get('batch_size', 25);
		$maxAttempts = max(1, (int) $params->get('max_attempts', 3));
		$retryMinutes = max(1, (int) $params->get('retry_minutes', 15));
		$this->recoverStaleClaims();
		$candidates = $this->loadCandidates(max(1, $batchSize));
		$result = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'retried' => 0, 'paused' => 0];
		$newsletterIds = [];

		foreach ($candidates as $candidate)
		{
			if (!$this->claim((int) $candidate->id))
			{
				continue;
			}

			$result['processed']++;
			$newsletterIds[(int) $candidate->newsletter_id] = true;
			$newsletter = $this->newsletters->find((int) $candidate->newsletter_id);

			if ($newsletter === null || $newsletter->snapshot_html === null)
			{
				$this->markFailure($candidate, 'Newsletter snapshot is missing.', $maxAttempts, $retryMinutes);
				$result['failed']++;
				continue;
			}

			try
			{
				$this->mail->sendNewsletter($newsletter, $candidate);
				$this->markSent((int) $candidate->id);
				$result['sent']++;
			}
			catch (\Throwable $e)
			{
				$permanent = ((int) $candidate->attempts + 1) >= $maxAttempts;
				$this->markFailure($candidate, $e->getMessage(), $maxAttempts, $retryMinutes);

				if ($permanent)
				{
					$result['failed']++;
				}
				else
				{
					$result['retried']++;
				}
			}
		}

		foreach (array_keys($newsletterIds) as $newsletterId)
		{
			$this->newsletters->refreshQueueCounters((int) $newsletterId);
		}

		return $result;
	}

	/**
	 * Returns due pending queue rows.
	 *
	 * @param int $limit Batch size.
	 *
	 * @return array<int,object> Queue rows.
	 */
	private function loadCandidates(int $limit): array
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->select('q.*')
			->from($this->db->quoteName('#__pungamail_send_queue', 'q'))
			->innerJoin($this->db->quoteName('#__pungamail_newsletters', 'n') . ' ON n.id = q.newsletter_id')
			->where($this->db->quoteName('q.status') . ' = ' . $this->db->quote('pending'))
			->where($this->db->quoteName('n.queue_paused') . ' = 0')
			->where('(' . $this->db->quoteName('q.next_attempt_at') . ' IS NULL OR ' . $this->db->quoteName('q.next_attempt_at') . ' <= :now)')
			->order($this->db->quoteName('q.id') . ' ASC')
			->bind(':now', $now);

		return $this->db->setQuery($query, 0, $limit)->loadObjectList();
	}

	/**
	 * Atomically claims one candidate if no other worker already claimed it.
	 *
	 * @param int $id Queue ID.
	 *
	 * @return bool True when this worker owns the row.
	 */
	private function claim(int $id): bool
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_send_queue'))
			->set($this->db->quoteName('status') . ' = ' . $this->db->quote('processing'))
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->where($this->db->quoteName('status') . ' = ' . $this->db->quote('pending'))
			->bind(':modified', $now)
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();

		return $this->db->getAffectedRows() === 1;
	}

	/**
	 * Marks a queue row sent.
	 *
	 * @param int $id Queue ID.
	 *
	 * @return void
	 */
	private function markSent(int $id): void
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_send_queue'))
			->set($this->db->quoteName('status') . ' = ' . $this->db->quote('sent'))
			->set($this->db->quoteName('attempts') . ' = ' . $this->db->quoteName('attempts') . ' + 1')
			->set($this->db->quoteName('last_error') . ' = NULL')
			->set($this->db->quoteName('failure_class') . ' = NULL')
			->set($this->db->quoteName('sent_at') . ' = :sentAt')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':sentAt', $now)
			->bind(':modified', $now)
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}

	/**
	 * Records a send failure and either schedules a retry or terminal failure.
	 *
	 * @param object $row          Queue row before the attempt.
	 * @param string $message      Error text.
	 * @param int    $maxAttempts  Maximum attempts.
	 * @param int    $retryMinutes Retry delay.
	 *
	 * @return void
	 */
	private function markFailure(object $row, string $message, int $maxAttempts, int $retryMinutes): void
	{
		$attempts = (int) $row->attempts + 1;
		$terminal = $attempts >= $maxAttempts;
		$now = (new Date('now', 'UTC'))->toSql();
		$next = $terminal ? null : (new Date('+' . $retryMinutes . ' minutes', 'UTC'))->toSql();
		$status = $terminal ? 'failed' : 'pending';
		$failureClass = $terminal ? 'permanent' : 'temporary';
		$error = mb_substr($message, 0, 4000, 'UTF-8');
		$rowId = (int) $row->id;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_send_queue'))
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('attempts') . ' = :attempts')
			->set($this->db->quoteName('next_attempt_at') . ' = ' . ($next === null ? 'NULL' : ':next'))
			->set($this->db->quoteName('last_error') . ' = :error')
			->set($this->db->quoteName('failure_class') . ' = :failureClass')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':status', $status)
			->bind(':attempts', $attempts, ParameterType::INTEGER)
			->bind(':error', $error)
			->bind(':failureClass', $failureClass)
			->bind(':modified', $now)
			->bind(':id', $rowId, ParameterType::INTEGER);

		if ($next !== null)
		{
			$query->bind(':next', $next);
		}

		$this->db->setQuery($query)->execute();
	}

	/**
	 * Recovers rows left in processing state by a terminated worker.
	 *
	 * @return void
	 */
	private function recoverStaleClaims(): void
	{
		$threshold = (new Date('-15 minutes', 'UTC'))->toSql();
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_send_queue'))
			->set($this->db->quoteName('status') . ' = ' . $this->db->quote('pending'))
			->set($this->db->quoteName('next_attempt_at') . ' = :now')
			->where($this->db->quoteName('status') . ' = ' . $this->db->quote('processing'))
			->where($this->db->quoteName('modified') . ' < :threshold')
			->bind(':now', $now)
			->bind(':threshold', $threshold);
		$this->db->setQuery($query)->execute();
	}
}
