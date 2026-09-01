<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Date\Date;
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
		private readonly RecipientResolver $recipients
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

		if ($newsletter === null || (int) $newsletter->status !== NewsletterRepository::STATUS_DRAFT)
		{
			throw new \RuntimeException('Only a draft newsletter can be queued.');
		}

		$items = $this->newsletters->getItems($newsletterId);
		$rendered = $this->renderer->render($newsletter, $items);
		$groupIds = $this->newsletters->getGroupIds($newsletterId);
		$recipients = $this->recipients->resolve($newsletter, $groupIds);

		if ($recipients === [])
		{
			throw new \RuntimeException('The newsletter has no eligible recipients.');
		}

		$now = (new Date('now', 'UTC'))->toSql();
		$this->db->transactionStart();

		try
		{
			$this->newsletters->freeze(
				$newsletterId,
				$rendered['subject'],
				$rendered['html'],
				$rendered['text'],
				$rendered['items'],
				$now
			);

			foreach ($recipients as $recipient)
			{
				$row = (object) [
					'newsletter_id' => $newsletterId,
					'subscriber_id' => $recipient['subscriber_id'],
					'user_id' => $recipient['user_id'],
					'email' => $recipient['email'],
					'email_normalized' => $recipient['email_normalized'],
					'source' => $recipient['source'],
					'status' => 'pending',
					'attempts' => 0,
					'next_attempt_at' => $now,
					'last_error' => null,
					'created' => $now,
					'modified' => $now,
					'sent_at' => null,
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
}
