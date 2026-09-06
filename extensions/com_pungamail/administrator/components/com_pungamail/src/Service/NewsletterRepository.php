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
 * Persistence service for newsletter drafts, selections and send snapshots.
 */
final class NewsletterRepository
{
	public const STATUS_DRAFT = 0;
	public const STATUS_QUEUED = 1;
	public const STATUS_SENDING = 2;
	public const STATUS_SENT = 3;
	public const STATUS_SENT_WITH_FAILURES = 4;
	public const STATUS_SCHEDULED = 5;
	public const STATUS_FAILED = 6;
	public const STATUS_CANCELLED = 7;

	/** @param DatabaseInterface $db Database connection. */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/** @return object|null */
	public function find(int $id): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/** @return array<int,object> */
	public function all(): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->order($this->db->quoteName('created') . ' DESC');

		return $this->db->setQuery($query)->loadObjectList();
	}

	/**
	 * Saves a mutable draft and replaces its selected content sources/items/groups.
	 *
	 * @param int                             $id                 Existing ID or zero.
	 * @param string                          $title              Internal title.
	 * @param string                          $subject            Mail subject.
	 * @param string                          $bodyMarkdown       Markdown body.
	 * @param bool                            $includeSubscribers Include confirmed subscribers.
	 * @param string|null                     $contentCutoffStart UTC content lower bound.
	 * @param array<int,array<string,mixed>>  $items              Selected content items.
	 * @param array<int,int>                  $groupIds           Joomla user groups.
	 * @param int                             $userId             Editing user.
	 * @param array<int,string>               $sourceKeys         Selected registered content types.
	 * @param int|null                        $templateId         Applied template ID.
	 * @param string|null                     $styleOverrides     Newsletter style override JSON.
	 * @param string                          $customCss          Newsletter custom CSS.
	 *
	 * @return int Newsletter ID.
	 */
	public function saveDraft(
		int $id,
		string $title,
		string $subject,
		string $bodyMarkdown,
		bool $includeSubscribers,
		?string $contentCutoffStart,
		array $items,
		array $groupIds,
		int $userId,
		array $sourceKeys = ['com_content.article'],
		?int $templateId = null,
		?string $styleOverrides = null,
		string $customCss = '',
		array $options = []
	): int
	{
		$existing = $id > 0 ? $this->find($id) : null;

		if ($existing !== null && !in_array((int) $existing->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED], true))
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_IMMUTABLE'));
		}

		$now = (new Date('now', 'UTC'))->toSql();
		$effectiveCutoff = $contentCutoffStart ?: ($existing?->content_cutoff_start ?: $this->getLastContentCutoff());
		$styleValue = $styleOverrides;
		$cssValue = $customCss !== '' ? $customCss : null;
		$headingMode = in_array((string) ($options['heading_mode'] ?? 'inherit'), ['inherit', 'custom', 'site', 'none'], true) ? (string) ($options['heading_mode'] ?? 'inherit') : 'inherit';
		$mailHeading = trim((string) ($options['mail_heading'] ?? ''));
		$browserView = in_array((int) ($options['browser_view'] ?? -1), [-1, 0, 1], true) ? (int) ($options['browser_view'] ?? -1) : -1;
		$replyToMode = in_array((string) ($options['reply_to_mode'] ?? 'inherit'), ['inherit', 'custom', 'none'], true) ? (string) ($options['reply_to_mode'] ?? 'inherit') : 'inherit';
		$replyToEmail = trim((string) ($options['reply_to_email'] ?? ''));
		$replyToName = trim((string) ($options['reply_to_name'] ?? ''));
		$newContentItemTemplate = trim((string) ($options['new_content_item_template'] ?? ''));
		$topicIds = (array) ($options['topic_ids'] ?? []);
		$this->db->transactionStart();

		try
		{
			if ($existing === null)
			{
				$row = (object) [
					'title' => $title,
					'subject' => $subject,
					'body_markdown' => $bodyMarkdown,
					'new_content_item_template' => $newContentItemTemplate !== '' ? $newContentItemTemplate : null,
					'state' => 1,
					'status' => self::STATUS_DRAFT,
					'template_id' => $templateId,
					'style_overrides' => $styleValue,
					'custom_css' => $cssValue,
					'heading_mode' => $headingMode,
					'mail_heading' => $mailHeading !== '' ? $mailHeading : null,
					'browser_view' => $browserView,
					'reply_to_mode' => $replyToMode,
					'reply_to_email' => $replyToEmail !== '' ? $replyToEmail : null,
					'reply_to_name' => $replyToName !== '' ? $replyToName : null,
					'include_subscribers' => $includeSubscribers ? 1 : 0,
					'content_cutoff_start' => $effectiveCutoff,
					'content_cutoff_end' => null,
					'snapshot_subject' => null,
					'snapshot_html' => null,
					'snapshot_text' => null,
					'snapshot_reply_to_email' => null,
					'snapshot_reply_to_name' => null,
					'snapshot_list_id' => null,
					'snapshot_browser_enabled' => 0,
					'snapshot_browser_token' => null,
					'recipient_count' => 0,
					'intended_count' => 0,
					'suppressed_count' => 0,
					'deduplicated_count' => 0,
					'invalid_count' => 0,
					'sent_count' => 0,
					'failed_count' => 0,
					'cancelled_count' => 0,
					'bounced_count' => 0,
					'created' => $now,
					'modified' => $now,
					'created_by' => $userId,
					'sent_at' => null,
					'scheduled_at' => null,
					'cancelled_at' => null,
					'queue_paused' => 0,
					'reminder_sent_at' => null,
				];
				$this->db->insertObject('#__pungamail_newsletters', $row, 'id');
				$id = (int) $row->id;
			}
			else
			{
				$query = $this->db->getQuery(true)
					->update($this->db->quoteName('#__pungamail_newsletters'))
					->set($this->db->quoteName('title') . ' = :title')
					->set($this->db->quoteName('subject') . ' = :subject')
					->set($this->db->quoteName('body_markdown') . ' = :body')
					->set($this->db->quoteName('new_content_item_template') . ($newContentItemTemplate === '' ? ' = NULL' : ' = :newContentItemTemplate'))
					->set($this->db->quoteName('template_id') . ($templateId === null ? ' = NULL' : ' = :templateId'))
					->set($this->db->quoteName('style_overrides') . ($styleValue === null ? ' = NULL' : ' = :styleOverrides'))
					->set($this->db->quoteName('custom_css') . ($cssValue === null ? ' = NULL' : ' = :customCss'))
					->set($this->db->quoteName('heading_mode') . ' = :headingMode')
					->set($this->db->quoteName('mail_heading') . ($mailHeading === '' ? ' = NULL' : ' = :mailHeading'))
					->set($this->db->quoteName('browser_view') . ' = :browserView')
					->set($this->db->quoteName('reply_to_mode') . ' = :replyToMode')
					->set($this->db->quoteName('reply_to_email') . ($replyToEmail === '' ? ' = NULL' : ' = :replyToEmail'))
					->set($this->db->quoteName('reply_to_name') . ($replyToName === '' ? ' = NULL' : ' = :replyToName'))
					->set($this->db->quoteName('include_subscribers') . ' = :includeSubscribers')
					->set($this->db->quoteName('content_cutoff_start') . ($effectiveCutoff === null ? ' = NULL' : ' = :contentCutoffStart'))
					->set($this->db->quoteName('modified') . ' = :modified')
					->where($this->db->quoteName('id') . ' = :id')
					->bind(':title', $title)
					->bind(':subject', $subject)
					->bind(':body', $bodyMarkdown)
					->bind(':includeSubscribers', $includeSubscribers, ParameterType::BOOLEAN)
					->bind(':headingMode', $headingMode)
					->bind(':browserView', $browserView, ParameterType::INTEGER)
					->bind(':replyToMode', $replyToMode)
					->bind(':modified', $now)
					->bind(':id', $id, ParameterType::INTEGER);

				if ($newContentItemTemplate !== '')
				{
					$query->bind(':newContentItemTemplate', $newContentItemTemplate);
				}

				if ($templateId !== null)
				{
					$query->bind(':templateId', $templateId, ParameterType::INTEGER);
				}

				if ($styleValue !== null)
				{
					$query->bind(':styleOverrides', $styleValue);
				}

				if ($cssValue !== null)
				{
					$query->bind(':customCss', $cssValue);
				}

				if ($effectiveCutoff !== null)
				{
					$query->bind(':contentCutoffStart', $effectiveCutoff);
				}

				if ($mailHeading !== '')
				{
					$query->bind(':mailHeading', $mailHeading);
				}

				if ($replyToEmail !== '')
				{
					$query->bind(':replyToEmail', $replyToEmail);
				}

				if ($replyToName !== '')
				{
					$query->bind(':replyToName', $replyToName);
				}

				$this->db->setQuery($query)->execute();
			}

			$this->replaceItems($id, $items);
			$this->replaceSources($id, $sourceKeys);
			$this->replaceGroups($id, $groupIds);
			$this->replaceTopics($id, $topicIds);
			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}

		return $id;
	}

	/** @return array<int,object> */
	public function getItems(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_newsletter_items'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->order($this->db->quoteName('ordering') . ' ASC')
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObjectList();
	}

	/** @return array<int,string> */
	public function getSourceKeys(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('source_key'))
			->from($this->db->quoteName('#__pungamail_newsletter_sources'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->order($this->db->quoteName('source_key') . ' ASC')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$keys = array_map('strval', $this->db->setQuery($query)->loadColumn());

		return $keys !== [] ? $keys : ['com_content.article'];
	}

	/** @return array<int,int> */
	public function getGroupIds(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('group_id'))
			->from($this->db->quoteName('#__pungamail_newsletter_groups'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return array_map('intval', $this->db->setQuery($query)->loadColumn());
	}

	/** @return array<int,int> */
	public function getTopicIds(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('topic_id'))
			->from($this->db->quoteName('#__pungamail_newsletter_topics'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return array_map('intval', $this->db->setQuery($query)->loadColumn());
	}

	/** @return array<int,object> */
	public function getTopics(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select('t.*')
			->from($this->db->quoteName('#__pungamail_topics', 't'))
			->innerJoin($this->db->quoteName('#__pungamail_newsletter_topics', 'nt') . ' ON nt.topic_id = t.id')
			->where($this->db->quoteName('nt.newsletter_id') . ' = :id')
			->order($this->db->quoteName('t.alias') . ' ASC')
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObjectList();
	}

	/** @return array<int,object> */
	public function getUserGroups(): array
	{
		$query = $this->db->getQuery(true)
			->select([$this->db->quoteName('id'), $this->db->quoteName('title')])
			->from($this->db->quoteName('#__usergroups'))
			->order($this->db->quoteName('lft') . ' ASC');

		return $this->db->setQuery($query)->loadObjectList();
	}

	/** @return string|null */
	public function getLastContentCutoff(): ?string
	{
		$query = $this->db->getQuery(true)
			->select('MAX(' . $this->db->quoteName('content_cutoff_end') . ')')
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->whereIn($this->db->quoteName('status'), [self::STATUS_SENT, self::STATUS_SENT_WITH_FAILURES]);
		$value = $this->db->setQuery($query)->loadResult();

		return $value !== null && $value !== '' ? (string) $value : null;
	}

	/**
	 * Stores immutable message and content-item snapshots.
	 *
	 * @param array<int,array<string,mixed>> $itemSnapshots Content snapshots.
	 */
	public function freeze(int $newsletterId, string $subject, string $html, string $text, array $itemSnapshots, string $cutoffEnd, array $metadata = []): void
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$queuedStatus = self::STATUS_QUEUED;
		$draftStatus = self::STATUS_DRAFT;
		$scheduledStatus = self::STATUS_SCHEDULED;
		$replyToEmail = trim((string) ($metadata['reply_to_email'] ?? ''));
		$replyToName = trim((string) ($metadata['reply_to_name'] ?? ''));
		$listId = trim((string) ($metadata['list_id'] ?? ''));
		$browserEnabled = (int) ($metadata['browser_enabled'] ?? 0) === 1 ? 1 : 0;
		$browserToken = $browserEnabled === 1 ? (string) ($metadata['browser_token'] ?? bin2hex(random_bytes(16))) : null;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('snapshot_subject') . ' = :subject')
			->set($this->db->quoteName('snapshot_html') . ' = :html')
			->set($this->db->quoteName('snapshot_text') . ' = :text')
			->set($this->db->quoteName('snapshot_reply_to_email') . ($replyToEmail === '' ? ' = NULL' : ' = :replyToEmail'))
			->set($this->db->quoteName('snapshot_reply_to_name') . ($replyToName === '' ? ' = NULL' : ' = :replyToName'))
			->set($this->db->quoteName('snapshot_list_id') . ($listId === '' ? ' = NULL' : ' = :listId'))
			->set($this->db->quoteName('snapshot_browser_enabled') . ' = :browserEnabled')
			->set($this->db->quoteName('snapshot_browser_token') . ($browserToken === null ? ' = NULL' : ' = :browserToken'))
			->set($this->db->quoteName('content_cutoff_end') . ' = :cutoff')
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->whereIn($this->db->quoteName('status'), [$draftStatus, $scheduledStatus])
			->bind(':subject', $subject)
			->bind(':html', $html)
			->bind(':text', $text)
			->bind(':browserEnabled', $browserEnabled, ParameterType::INTEGER)
			->bind(':cutoff', $cutoffEnd)
			->bind(':status', $queuedStatus, ParameterType::INTEGER)
			->bind(':modified', $now)
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		if ($replyToEmail !== '')
		{
			$query->bind(':replyToEmail', $replyToEmail);
		}

		if ($replyToName !== '')
		{
			$query->bind(':replyToName', $replyToName);
		}

		if ($listId !== '')
		{
			$query->bind(':listId', $listId);
		}

		if ($browserToken !== null)
		{
			$query->bind(':browserToken', $browserToken);
		}
		$this->db->setQuery($query)->execute();

		if ($this->db->getAffectedRows() !== 1)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_FREEZE_NOT_DRAFT'));
		}

		foreach ($itemSnapshots as $item)
		{
			$snapshotTitle = (string) $item['title'];
			$snapshotExcerpt = (string) $item['excerpt'];
			$snapshotUrl = (string) $item['url'];
			$sourceKey = (string) $item['source_key'];
			$sourceItemId = (string) $item['source_item_id'];
			$update = $this->db->getQuery(true)
				->update($this->db->quoteName('#__pungamail_newsletter_items'))
				->set($this->db->quoteName('snapshot_title') . ' = :title')
				->set($this->db->quoteName('snapshot_excerpt') . ' = :excerpt')
				->set($this->db->quoteName('snapshot_url') . ' = :url')
				->where($this->db->quoteName('newsletter_id') . ' = :newsletterId')
				->where($this->db->quoteName('source_key') . ' = :sourceKey')
				->where($this->db->quoteName('source_item_id') . ' = :sourceItemId')
				->bind(':title', $snapshotTitle)
				->bind(':excerpt', $snapshotExcerpt)
				->bind(':url', $snapshotUrl)
				->bind(':newsletterId', $newsletterId, ParameterType::INTEGER)
				->bind(':sourceKey', $sourceKey)
				->bind(':sourceItemId', $sourceItemId);
			$this->db->setQuery($update)->execute();
		}
	}

	/** @return void */
	public function refreshQueueCounters(int $newsletterId): void
	{
		$query = $this->db->getQuery(true)
			->select([
				'COUNT(*) AS total',
				"SUM(CASE WHEN status IN ('sent', 'bounced') THEN 1 ELSE 0 END) AS sent_count",
				"SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count",
				"SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) AS open_count",
				"SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count",
				"SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) AS bounced_count",
			])
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$counts = $this->db->setQuery($query)->loadObject();

		if (!$counts)
		{
			return;
		}

		$total = (int) $counts->total;
		$sentCount = (int) $counts->sent_count;
		$failedCount = (int) $counts->failed_count;
		$openCount = (int) $counts->open_count;
		$cancelledCount = (int) $counts->cancelled_count;
		$bouncedCount = (int) $counts->bounced_count;
		$status = $openCount > 0
			? self::STATUS_SENDING
			: ($cancelledCount > 0 ? self::STATUS_CANCELLED : ($failedCount > 0 || $bouncedCount > 0 ? self::STATUS_SENT_WITH_FAILURES : self::STATUS_SENT));
		$sentAt = $openCount === 0 ? (new Date('now', 'UTC'))->toSql() : null;
		$update = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('recipient_count') . ' = :total')
			->set($this->db->quoteName('sent_count') . ' = :sent')
			->set($this->db->quoteName('failed_count') . ' = :failed')
			->set($this->db->quoteName('cancelled_count') . ' = :cancelled')
			->set($this->db->quoteName('bounced_count') . ' = :bounced')
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('sent_at') . ' = ' . ($sentAt === null ? $this->db->quoteName('sent_at') : ':sentAt'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':total', $total, ParameterType::INTEGER)
			->bind(':sent', $sentCount, ParameterType::INTEGER)
			->bind(':failed', $failedCount, ParameterType::INTEGER)
			->bind(':cancelled', $cancelledCount, ParameterType::INTEGER)
			->bind(':bounced', $bouncedCount, ParameterType::INTEGER)
			->bind(':status', $status, ParameterType::INTEGER)
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		if ($sentAt !== null)
		{
			$update->bind(':sentAt', $sentAt);
		}

		$this->db->setQuery($update)->execute();
	}

	/** @return array<int,object> */
	public function getQueueRecipients(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select(['q.*', 'b.classification AS bounce_classification', 'b.status_code AS bounce_status_code', 'b.diagnostic AS bounce_diagnostic'])
			->from($this->db->quoteName('#__pungamail_send_queue', 'q'))
			->leftJoin($this->db->quoteName('#__pungamail_bounces', 'b') . ' ON b.id = q.bounce_id')
			->where($this->db->quoteName('q.newsletter_id') . ' = :id')
			->order($this->db->quoteName('q.email_normalized') . ' ASC')
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObjectList();
	}

	/** @param array<string,int> $counts Recipient-resolution counts. @return void */
	public function setResolutionCounts(int $newsletterId, array $counts): void
	{
		$intended = max(0, (int) ($counts['intended'] ?? 0));
		$suppressed = max(0, (int) ($counts['suppressed'] ?? 0));
		$deduplicated = max(0, (int) ($counts['deduplicated'] ?? 0));
		$invalid = max(0, (int) ($counts['invalid'] ?? 0));
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('intended_count') . ' = :intended')
			->set($this->db->quoteName('suppressed_count') . ' = :suppressed')
			->set($this->db->quoteName('deduplicated_count') . ' = :deduplicated')
			->set($this->db->quoteName('invalid_count') . ' = :invalid')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':intended', $intended, ParameterType::INTEGER)
			->bind(':suppressed', $suppressed, ParameterType::INTEGER)
			->bind(':deduplicated', $deduplicated, ParameterType::INTEGER)
			->bind(':invalid', $invalid, ParameterType::INTEGER)
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}

	/** @return void */
	public function setState(array $ids, int $state): void
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return;
		}

		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('state') . ' = :state')
			->whereIn($this->db->quoteName('id'), $ids)
			->bind(':state', $state, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}

	/** @return int */
	public function deleteTrashed(array $ids): int
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return 0;
		}

		$trashedState = -2;
		$select = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->whereIn($this->db->quoteName('id'), $ids)
			->where($this->db->quoteName('state') . ' = :trashed')
			->bind(':trashed', $trashedState, ParameterType::INTEGER);
		$deleteIds = array_map('intval', $this->db->setQuery($select)->loadColumn());

		if ($deleteIds === [])
		{
			return 0;
		}

		$this->db->transactionStart();

		try
		{
			foreach (['#__pungamail_send_queue', '#__pungamail_newsletter_items', '#__pungamail_newsletter_sources', '#__pungamail_newsletter_groups', '#__pungamail_newsletter_topics'] as $table)
			{
				$deleteRelated = $this->db->getQuery(true)->delete($this->db->quoteName($table))->whereIn($this->db->quoteName('newsletter_id'), $deleteIds);
				$this->db->setQuery($deleteRelated)->execute();
			}

			$delete = $this->db->getQuery(true)->delete($this->db->quoteName('#__pungamail_newsletters'))->whereIn($this->db->quoteName('id'), $deleteIds);
			$this->db->setQuery($delete)->execute();
			$count = $this->db->getAffectedRows();
			$this->db->transactionCommit();

			return $count;
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}
	}

	/** @param array<int,array<string,mixed>> $items @return void */
	private function replaceItems(int $newsletterId, array $items): void
	{
		$delete = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_newsletter_items'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($delete)->execute();
		$order = 0;

		foreach ($items as $item)
		{
			$sourceKey = trim((string) ($item['source_key'] ?? ''));
			$sourceItemId = trim((string) ($item['source_item_id'] ?? ''));

			if ($sourceKey === '' || $sourceItemId === '')
			{
				continue;
			}

			$row = (object) [
				'newsletter_id' => $newsletterId,
				'source_key' => $sourceKey,
				'source_item_id' => $sourceItemId,
				'ordering' => $order++,
				'title_override' => trim((string) ($item['title_override'] ?? '')) ?: null,
				'excerpt_override' => trim((string) ($item['excerpt_override'] ?? '')) ?: null,
				'snapshot_title' => null,
				'snapshot_excerpt' => null,
				'snapshot_url' => null,
			];
			$this->db->insertObject('#__pungamail_newsletter_items', $row);
		}
	}

	/** @return void */
	private function replaceSources(int $newsletterId, array $sourceKeys): void
	{
		$delete = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_newsletter_sources'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($delete)->execute();

		foreach (array_values(array_unique(array_filter(array_map('strval', $sourceKeys)))) as $sourceKey)
		{
			$row = (object) ['newsletter_id' => $newsletterId, 'source_key' => $sourceKey];
			$this->db->insertObject('#__pungamail_newsletter_sources', $row);
		}
	}

	/** @return void */
	private function replaceGroups(int $newsletterId, array $groupIds): void
	{
		$delete = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_newsletter_groups'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($delete)->execute();

		foreach (array_values(array_unique(array_filter(array_map('intval', $groupIds)))) as $groupId)
		{
			$row = (object) ['newsletter_id' => $newsletterId, 'group_id' => $groupId];
			$this->db->insertObject('#__pungamail_newsletter_groups', $row);
		}
	}

	/** @return void */
	private function replaceTopics(int $newsletterId, array $topicIds): void
	{
		$delete = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_newsletter_topics'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($delete)->execute();

		foreach (array_values(array_unique(array_filter(array_map('intval', $topicIds)))) as $topicId)
		{
			$row = (object) ['newsletter_id' => $newsletterId, 'topic_id' => $topicId];
			$this->db->insertObject('#__pungamail_newsletter_topics', $row);
		}
	}

	/**
	 * Creates an independent draft copy of an existing newsletter.
	 *
	 * @param int $newsletterId Source newsletter ID.
	 * @param int $userId       User creating the copy.
	 *
	 * @return int New newsletter ID.
	 */
	public function duplicateAsDraft(int $newsletterId, int $userId): int
	{
		$newsletter = $this->find($newsletterId);

		if ($newsletter === null)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_NEWSLETTER_NOT_FOUND'));
		}

		$items = [];

		foreach ($this->getItems($newsletterId) as $item)
		{
			$items[] = [
				'source_key' => (string) $item->source_key,
				'source_item_id' => (string) $item->source_item_id,
				'title_override' => (string) ($item->title_override ?? ''),
				'excerpt_override' => (string) ($item->excerpt_override ?? ''),
				'ordering' => (int) $item->ordering,
			];
		}

		return $this->saveDraft(
			0,
			Text::sprintf('COM_PUNGAMAIL_COPY_TITLE', (string) $newsletter->title),
			(string) $newsletter->subject,
			(string) $newsletter->body_markdown,
			(bool) $newsletter->include_subscribers,
			$newsletter->content_cutoff_start !== null ? (string) $newsletter->content_cutoff_start : null,
			$items,
			$this->getGroupIds($newsletterId),
			$userId,
			$this->getSourceKeys($newsletterId),
			$newsletter->template_id !== null ? (int) $newsletter->template_id : null,
			$newsletter->style_overrides !== null ? (string) $newsletter->style_overrides : null,
			(string) ($newsletter->custom_css ?? ''),
			[
				'topic_ids' => $this->getTopicIds($newsletterId),
				'heading_mode' => (string) ($newsletter->heading_mode ?? 'inherit'),
				'mail_heading' => (string) ($newsletter->mail_heading ?? ''),
				'browser_view' => (int) ($newsletter->browser_view ?? -1),
				'reply_to_mode' => (string) ($newsletter->reply_to_mode ?? 'inherit'),
				'reply_to_email' => (string) ($newsletter->reply_to_email ?? ''),
				'reply_to_name' => (string) ($newsletter->reply_to_name ?? ''),
			]
		);
	}

	/** @return void */
	public function schedule(int $newsletterId, string $scheduledAt): void
	{
		$newsletter = $this->find($newsletterId);

		if ($newsletter === null || !in_array((int) $newsletter->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED], true))
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_SCHEDULE_DRAFT_ONLY'));
		}

		$status = self::STATUS_SCHEDULED;
		$draft = self::STATUS_DRAFT;
		$scheduled = self::STATUS_SCHEDULED;
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('scheduled_at') . ' = :scheduledAt')
			->set($this->db->quoteName('cancelled_at') . ' = NULL')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->whereIn($this->db->quoteName('status'), [$draft, $scheduled])
			->bind(':status', $status, ParameterType::INTEGER)
			->bind(':scheduledAt', $scheduledAt)
			->bind(':modified', $now)
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}

	/** @return void */
	public function cancelScheduled(int $newsletterId): void
	{
		$status = self::STATUS_DRAFT;
		$scheduled = self::STATUS_SCHEDULED;
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('scheduled_at') . ' = NULL')
			->set($this->db->quoteName('cancelled_at') . ' = NULL')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->where($this->db->quoteName('status') . ' = :scheduled')
			->bind(':status', $status, ParameterType::INTEGER)
			->bind(':modified', $now)
			->bind(':id', $newsletterId, ParameterType::INTEGER)
			->bind(':scheduled', $scheduled, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}

	/** @return array<int,object> */
	public function dueScheduled(int $limit = 20): array
	{
		$status = self::STATUS_SCHEDULED;
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->where($this->db->quoteName('status') . ' = :status')
			->where($this->db->quoteName('scheduled_at') . ' <= :now')
			->order($this->db->quoteName('scheduled_at') . ' ASC')
			->bind(':status', $status, ParameterType::INTEGER)
			->bind(':now', $now);

		return $this->db->setQuery($query, 0, max(1, $limit))->loadObjectList();
	}

	/** @return void */
	public function markSchedulingFailed(int $newsletterId, string $message): void
	{
		$status = self::STATUS_FAILED;
		$now = (new Date('now', 'UTC'))->toSql();
		$error = mb_substr($message, 0, 4000, 'UTF-8');
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':status', $status, ParameterType::INTEGER)
			->bind(':modified', $now)
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
		$row = (object) [
			'subscriber_id' => null,
			'event_type' => 'scheduled_send_failed',
			'newsletter_id' => $newsletterId,
			'ip_address' => null,
			'user_agent' => null,
			'metadata' => json_encode(['error' => $error], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			'created' => $now,
		];
		$this->db->insertObject('#__pungamail_events', $row, 'id');
	}

	/** @return void */
	public function setQueuePaused(int $newsletterId, bool $paused): void
	{
		$value = $paused ? 1 : 0;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('queue_paused') . ' = :paused')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':paused', $value, ParameterType::INTEGER)
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}

	/** @return int */
	public function cancelRemaining(int $newsletterId): int
	{
		$status = 'cancelled';
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_send_queue'))
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('cancelled_at') . ' = :cancelledAt')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->where($this->db->quoteName('status') . ' = ' . $this->db->quote('pending'))
			->bind(':status', $status)
			->bind(':cancelledAt', $now)
			->bind(':modified', $now)
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
		$count = $this->db->getAffectedRows();
		$this->refreshQueueCounters($newsletterId);

		return $count;
	}
}
