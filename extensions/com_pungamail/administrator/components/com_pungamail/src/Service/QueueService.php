<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Freezes newsletters and creates idempotent recipient queue snapshots.
 */
final class QueueService
{
	/**
	 * @param DatabaseInterface    $db          Database connection.
	 * @param NewsletterRepository $newsletters Newsletter repository.
	 * @param NewsletterRenderer   $renderer    Newsletter renderer.
	 * @param RecipientResolver    $recipients  Recipient resolver.
	 */
	public function __construct(
		private readonly DatabaseInterface $db,
		private readonly NewsletterRepository $newsletters,
		private readonly NewsletterRenderer $renderer,
		private readonly RecipientResolver $recipients,
		private readonly PreflightService $preflight,
		private readonly MailConfigurationService $mailConfiguration,
		private readonly TemplateRepository $templates
	)
	{
	}

	/**
	 * Freezes a draft and creates its queue in a single transaction.
	 *
	 * @param int $newsletterId Newsletter ID.
	 *
	 * @return int Number of unique queued recipients.
	 */
	public function queue(int $newsletterId): int
	{
		$newsletter = $this->newsletters->find($newsletterId);

		if ($newsletter === null || !in_array((int) $newsletter->status, [NewsletterRepository::STATUS_DRAFT, NewsletterRepository::STATUS_SCHEDULED], true))
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_QUEUE_DRAFT_ONLY'));
		}

		$preflight = $this->preflight->assertSendable($newsletterId);
		$items = $preflight['items'];
		$rendered = $preflight['rendered'];
		$groupIds = $this->newsletters->getGroupIds($newsletterId);
		$topicIds = $this->newsletters->getTopicIds($newsletterId);
		$recipientReport = $this->recipients->resolveWithReport($newsletter, $groupIds, $topicIds, true);
		$recipients = $recipientReport['recipients'];

		if ($recipients === [])
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_NO_RECIPIENTS'));
		}

		$now = (new Date('now', 'UTC'))->toSql();
		$template = isset($newsletter->template_id) && (int) $newsletter->template_id > 0
			? $this->templates->find((int) $newsletter->template_id)
			: null;
		$replyTo = $this->mailConfiguration->replyTo($template, $newsletter);
		$topics = $this->newsletters->getTopics($newsletterId);
		$this->db->transactionStart();

		try
		{
			$this->newsletters->freeze(
				$newsletterId,
				$rendered['subject'],
				$rendered['html'],
				$rendered['text'],
				$rendered['items'],
				$now,
				[
					'reply_to_email' => $replyTo['email'],
					'reply_to_name' => $replyTo['name'],
					'list_id' => $this->mailConfiguration->listId($topics),
					'browser_enabled' => $this->mailConfiguration->browserView($template, $newsletter) ? 1 : 0,
				]
			);
			$this->newsletters->setResolutionCounts($newsletterId, $recipientReport['counts']);

			foreach ($recipients as $recipient)
			{
				$row = (object) [
					'newsletter_id' => $newsletterId,
					'subscriber_id' => $recipient['subscriber_id'],
					'user_id' => $recipient['user_id'],
					'email' => $recipient['email'],
					'recipient_name' => $recipient['recipient_name'],
					'email_normalized' => $recipient['email_normalized'],
					'source' => $recipient['source'],
					'status' => 'pending',
					'attempts' => 0,
					'next_attempt_at' => $now,
					'last_error' => null,
					'failure_class' => null,
					'created' => $now,
					'modified' => $now,
					'sent_at' => null,
					'cancelled_at' => null,
					'bounce_id' => null,
				];
				$this->db->insertObject('#__pungamail_send_queue', $row, 'id');
			}

			$this->newsletters->refreshQueueCounters($newsletterId);
			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}

		return count($recipients);
	}
	/**
	 * Cancels pending/failed queue rows for a subscriber before the live record is removed.
	 *
	 * Historical sent/cancelled rows remain immutable. A currently processing row
	 * cannot safely be cancelled here and is reported to the caller.
	 *
	 * @param int $subscriberId Subscriber ID.
	 *
	 * @return bool False when a row is currently being processed.
	 */
	public function cancelForSubscriber(int $subscriberId): bool
	{
		if ($subscriberId <= 0)
		{
			return false;
		}

		$select = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->where($this->db->quoteName('subscriber_id') . ' = :subscriberId')
			->whereIn($this->db->quoteName('status'), ['pending', 'failed'], ParameterType::STRING)
			->bind(':subscriberId', $subscriberId, ParameterType::INTEGER);
		$ids = array_map('intval', $this->db->setQuery($select)->loadColumn());

		if ($ids !== [])
		{
			$this->cancel($ids);
		}

		// Check after cancellation so a worker that claimed a pending row while the
		// delete action was running is detected before the subscriber is removed.
		$processing = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->where($this->db->quoteName('subscriber_id') . ' = :subscriberId')
			->where($this->db->quoteName('status') . ' = ' . $this->db->quote('processing'))
			->bind(':subscriberId', $subscriberId, ParameterType::INTEGER);

		return (int) $this->db->setQuery($processing)->loadResult() === 0;
	}

	/**
	 * Resets failed queue entries so the queue processor can try them again.
	 *
	 * @param array<int,int> $ids Queue row IDs.
	 *
	 * @return int Number of entries reset.
	 */
	public function retryFailed(array $ids): int
	{
		return $this->updateQueueRows($ids, 'failed', 'pending', true);
	}

	/**
	 * Cancels pending or failed queue entries without touching already delivered mail.
	 *
	 * @param array<int,int> $ids Queue row IDs.
	 *
	 * @return int Number of entries cancelled.
	 */
	public function cancel(array $ids): int
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return 0;
		}

		$statuses = ['pending', 'failed'];
		$select = $this->db->getQuery(true)
			->select([$this->db->quoteName('id'), $this->db->quoteName('newsletter_id')])
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->whereIn($this->db->quoteName('id'), $ids)
			->whereIn($this->db->quoteName('status'), $statuses, ParameterType::STRING);
		$rows = $this->db->setQuery($select)->loadObjectList();

		if ($rows === [])
		{
			return 0;
		}

		$queueIds = array_map(static fn (object $row): int => (int) $row->id, $rows);
		$newsletterIds = array_values(array_unique(array_map(static fn (object $row): int => (int) $row->newsletter_id, $rows)));
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_send_queue'))
			->set($this->db->quoteName('status') . ' = ' . $this->db->quote('cancelled'))
			->set($this->db->quoteName('cancelled_at') . ' = :cancelledAt')
			->set($this->db->quoteName('modified') . ' = :modified')
			->whereIn($this->db->quoteName('id'), $queueIds)
			->bind(':cancelledAt', $now)
			->bind(':modified', $now);
		$this->db->setQuery($query)->execute();
		$count = $this->db->getAffectedRows();

		foreach ($newsletterIds as $newsletterId)
		{
			$this->newsletters->refreshQueueCounters($newsletterId);
		}

		return $count;
	}

	/**
	 * Archives completed queue entries without changing their delivery status.
	 *
	 * Pending or currently processing rows are deliberately excluded so an
	 * archived row can never continue sending invisibly in the background.
	 *
	 * @param array<int,int> $ids Queue row IDs.
	 *
	 * @return int Number of entries archived.
	 */
	public function archive(array $ids): int
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return 0;
		}

		$terminalStatuses = ['sent', 'failed', 'cancelled', 'bounced'];
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_send_queue'))
			->set($this->db->quoteName('archived') . ' = 1')
			->set($this->db->quoteName('archived_at') . ' = :archivedAt')
			->whereIn($this->db->quoteName('id'), $ids)
			->where($this->db->quoteName('archived') . ' = 0')
			->whereIn($this->db->quoteName('status'), $terminalStatuses, ParameterType::STRING)
			->bind(':archivedAt', $now);
		$this->db->setQuery($query)->execute();

		return $this->db->getAffectedRows();
	}

	/**
	 * Restores archived queue entries to the normal Delivery history view.
	 *
	 * @param array<int,int> $ids Queue row IDs.
	 *
	 * @return int Number of entries restored.
	 */
	public function unarchive(array $ids): int
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return 0;
		}

		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_send_queue'))
			->set($this->db->quoteName('archived') . ' = 0')
			->set($this->db->quoteName('archived_at') . ' = NULL')
			->whereIn($this->db->quoteName('id'), $ids)
			->where($this->db->quoteName('archived') . ' = 1');
		$this->db->setQuery($query)->execute();

		return $this->db->getAffectedRows();
	}

	/**
	 * Changes queue rows from one state to another and refreshes parent counters.
	 *
	 * @param array<int,int> $ids           Queue row IDs.
	 * @param string         $requiredStatus Required current status.
	 * @param string         $newStatus      New queue status.
	 * @param bool           $resetAttempts  Whether retry metadata should be reset.
	 *
	 * @return int Number of changed rows.
	 */
	private function updateQueueRows(array $ids, string $requiredStatus, string $newStatus, bool $resetAttempts): int
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return 0;
		}

		$select = $this->db->getQuery(true)
			->select([$this->db->quoteName('id'), $this->db->quoteName('newsletter_id')])
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->whereIn($this->db->quoteName('id'), $ids)
			->where($this->db->quoteName('status') . ' = :status')
			->bind(':status', $requiredStatus);
		$rows = $this->db->setQuery($select)->loadObjectList();

		if ($rows === [])
		{
			return 0;
		}

		$queueIds = array_map(static fn (object $row): int => (int) $row->id, $rows);
		$newsletterIds = array_values(array_unique(array_map(static fn (object $row): int => (int) $row->newsletter_id, $rows)));
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_send_queue'))
			->set($this->db->quoteName('status') . ' = :newStatus')
			->set($this->db->quoteName('modified') . ' = :modified')
			->set($this->db->quoteName('next_attempt_at') . ' = :nextAttempt')
			->set($this->db->quoteName('last_error') . ' = NULL')
			->set($this->db->quoteName('failure_class') . ' = NULL')
			->set($this->db->quoteName('cancelled_at') . ' = NULL')
			->whereIn($this->db->quoteName('id'), $queueIds)
			->bind(':newStatus', $newStatus)
			->bind(':modified', $now)
			->bind(':nextAttempt', $now);

		if ($resetAttempts)
		{
			$query->set($this->db->quoteName('attempts') . ' = 0');
		}

		if ($newStatus === 'pending')
		{
			$query->set($this->db->quoteName('archived') . ' = 0')
				->set($this->db->quoteName('archived_at') . ' = NULL');
		}

		$this->db->setQuery($query)->execute();
		$count = $this->db->getAffectedRows();

		foreach ($newsletterIds as $newsletterId)
		{
			$this->newsletters->refreshQueueCounters($newsletterId);
		}

		return $count;
	}

}
