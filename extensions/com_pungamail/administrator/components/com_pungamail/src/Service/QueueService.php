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
}
