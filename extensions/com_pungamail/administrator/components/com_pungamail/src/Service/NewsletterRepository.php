<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Persistence and content-query operations for newsletters.
 */
final class NewsletterRepository
{
	public const STATUS_DRAFT = 0;
	public const STATUS_QUEUED = 1;
	public const STATUS_SENDING = 2;
	public const STATUS_SENT = 3;
	public const STATUS_SENT_WITH_FAILURES = 4;

	/**
	 * @param DatabaseInterface $db Database connection.
	 */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/**
	 * Returns one newsletter.
	 *
	 * @param int $id Newsletter ID.
	 *
	 * @return object|null Newsletter or null.
	 */
	public function find(int $id): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/**
	 * Lists newsletters newest first.
	 *
	 * @return array<int,object> Newsletter rows.
	 */
	public function all(): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->order($this->db->quoteName('created') . ' DESC');

		return $this->db->setQuery($query)->loadObjectList();
	}

	/**
	 * Saves a draft and replaces its article/group selections transactionally.
	 *
	 * @param int          $id                 Existing ID or zero for a new draft.
	 * @param string       $title              Internal newsletter title.
	 * @param string       $subject            Email subject.
	 * @param string       $bodyMarkdown       Markdown body.
	 * @param bool         $includeSubscribers Include opted-in subscribers.
	 * @param array<int,array<string,mixed>> $items Selected article data.
	 * @param array<int,int> $groupIds          Joomla user-group IDs.
	 * @param int          $userId              Editing user ID.
	 *
	 * @return int Saved newsletter ID.
	 */
	public function saveDraft(
		int $id,
		string $title,
		string $subject,
		string $bodyMarkdown,
		bool $includeSubscribers,
		array $items,
		array $groupIds,
		int $userId
	): int
	{
		$existing = $id > 0 ? $this->find($id) : null;

		if ($existing !== null && (int) $existing->status !== self::STATUS_DRAFT)
		{
			throw new \RuntimeException('A queued or sent newsletter is immutable. Duplicate it to make changes.');
		}

		$now = (new Date('now', 'UTC'))->toSql();
		$this->db->transactionStart();

		try
		{
			if ($existing === null)
			{
				$row = (object) [
					'title' => $title,
					'subject' => $subject,
					'body_markdown' => $bodyMarkdown,
					'status' => self::STATUS_DRAFT,
					'include_subscribers' => $includeSubscribers ? 1 : 0,
					'content_cutoff_start' => $this->getLastContentCutoff(),
					'content_cutoff_end' => null,
					'snapshot_subject' => null,
					'snapshot_html' => null,
					'snapshot_text' => null,
					'recipient_count' => 0,
					'sent_count' => 0,
					'failed_count' => 0,
					'created' => $now,
					'modified' => $now,
					'created_by' => $userId,
					'sent_at' => null,
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
					->set($this->db->quoteName('include_subscribers') . ' = :includeSubscribers')
					->set($this->db->quoteName('modified') . ' = :modified')
					->where($this->db->quoteName('id') . ' = :id')
					->bind(':title', $title)
					->bind(':subject', $subject)
					->bind(':body', $bodyMarkdown)
					->bind(':includeSubscribers', $includeSubscribers, ParameterType::BOOLEAN)
					->bind(':modified', $now)
					->bind(':id', $id, ParameterType::INTEGER);
				$this->db->setQuery($query)->execute();
			}

			$this->replaceItems($id, $items);
			$this->replaceGroups($id, $groupIds);
			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}

		return $id;
	}

	/**
	 * Returns article selections stored for a newsletter.
	 *
	 * @param int $newsletterId Newsletter ID.
	 *
	 * @return array<int,object> Selected item rows joined to current content.
	 */
	public function getItems(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select([
				'i.*',
				'c.title AS article_title',
				'c.introtext AS article_introtext',
				'c.catid',
				'c.alias',
			])
			->from($this->db->quoteName('#__pungamail_newsletter_items', 'i'))
			->leftJoin($this->db->quoteName('#__content', 'c') . ' ON c.id = i.content_id')
			->where('i.newsletter_id = :id')
			->order('i.ordering ASC')
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObjectList();
	}

	/**
	 * Returns selected user groups.
	 *
	 * @param int $newsletterId Newsletter ID.
	 *
	 * @return array<int,int> Group IDs.
	 */
	public function getGroupIds(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('group_id'))
			->from($this->db->quoteName('#__pungamail_newsletter_groups'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return array_map('intval', $this->db->setQuery($query)->loadColumn());
	}

	/**
	 * Returns published Joomla articles newer than a newsletter's cutoff.
	 *
	 * @param string|null $cutoff UTC SQL timestamp or null for all published content.
	 * @param int         $limit  Maximum result count.
	 *
	 * @return array<int,object> Content rows.
	 */
	public function getAvailableArticles(?string $cutoff, int $limit = 100): array
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$publishedExpression = 'COALESCE(' . $this->db->quoteName('c.publish_up') . ', ' . $this->db->quoteName('c.created') . ')';
		$query = $this->db->getQuery(true)
			->select([
				'c.id',
				'c.title',
				'c.introtext',
				'c.catid',
				'c.alias',
				'c.publish_up',
				'c.created',
				'cat.title AS category_title',
			])
			->from($this->db->quoteName('#__content', 'c'))
			->leftJoin($this->db->quoteName('#__categories', 'cat') . ' ON cat.id = c.catid')
			->where('c.state = 1')
			->where('(' . $this->db->quoteName('c.publish_up') . ' IS NULL OR c.publish_up <= :now)')
			->where('(' . $this->db->quoteName('c.publish_down') . ' IS NULL OR c.publish_down >= :now2)')
			->order($publishedExpression . ' DESC')
			->bind(':now', $now)
			->bind(':now2', $now);

		if ($cutoff !== null && $cutoff !== '')
		{
			$query->where($publishedExpression . ' > :cutoff')->bind(':cutoff', $cutoff);
		}

		return $this->db->setQuery($query, 0, max(1, $limit))->loadObjectList();
	}

	/**
	 * Returns Joomla user groups for recipient targeting.
	 *
	 * @return array<int,object> Group rows.
	 */
	public function getUserGroups(): array
	{
		$query = $this->db->getQuery(true)
			->select([$this->db->quoteName('id'), $this->db->quoteName('title')])
			->from($this->db->quoteName('#__usergroups'))
			->order($this->db->quoteName('lft') . ' ASC');

		return $this->db->setQuery($query)->loadObjectList();
	}

	/**
	 * Returns the latest cutoff of a completed newsletter.
	 *
	 * @return string|null UTC SQL timestamp.
	 */
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
	 * Stores immutable render snapshots and article snapshots.
	 *
	 * @param int    $newsletterId Newsletter ID.
	 * @param string $subject      Frozen subject.
	 * @param string $html         Frozen HTML containing recipient placeholders.
	 * @param string $text         Frozen plain text containing recipient placeholders.
	 * @param array<int,array<string,mixed>> $itemSnapshots Item snapshots.
	 * @param string $cutoffEnd    UTC content cutoff end.
	 *
	 * @return void
	 */
	public function freeze(int $newsletterId, string $subject, string $html, string $text, array $itemSnapshots, string $cutoffEnd): void
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$queuedStatus = self::STATUS_QUEUED;
		$draftStatus = self::STATUS_DRAFT;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('snapshot_subject') . ' = :subject')
			->set($this->db->quoteName('snapshot_html') . ' = :html')
			->set($this->db->quoteName('snapshot_text') . ' = :text')
			->set($this->db->quoteName('content_cutoff_end') . ' = :cutoff')
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->where($this->db->quoteName('status') . ' = :draft')
			->bind(':subject', $subject)
			->bind(':html', $html)
			->bind(':text', $text)
			->bind(':cutoff', $cutoffEnd)
			->bind(':status', $queuedStatus, ParameterType::INTEGER)
			->bind(':modified', $now)
			->bind(':id', $newsletterId, ParameterType::INTEGER)
			->bind(':draft', $draftStatus, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();

		if ($this->db->getAffectedRows() !== 1)
		{
			throw new \RuntimeException('Newsletter could not be frozen because it is no longer a draft.');
		}

		foreach ($itemSnapshots as $item)
		{
			$snapshotTitle = (string) $item['title'];
			$snapshotExcerpt = (string) $item['excerpt'];
			$snapshotUrl = (string) $item['url'];
			$contentId = (int) $item['content_id'];
			$update = $this->db->getQuery(true)
				->update($this->db->quoteName('#__pungamail_newsletter_items'))
				->set($this->db->quoteName('snapshot_title') . ' = :title')
				->set($this->db->quoteName('snapshot_excerpt') . ' = :excerpt')
				->set($this->db->quoteName('snapshot_url') . ' = :url')
				->where($this->db->quoteName('newsletter_id') . ' = :newsletterId')
				->where($this->db->quoteName('content_id') . ' = :contentId')
				->bind(':title', $snapshotTitle)
				->bind(':excerpt', $snapshotExcerpt)
				->bind(':url', $snapshotUrl)
				->bind(':newsletterId', $newsletterId, ParameterType::INTEGER)
				->bind(':contentId', $contentId, ParameterType::INTEGER);
			$this->db->setQuery($update)->execute();
		}
	}

	/**
	 * Updates cached newsletter queue counters and terminal status.
	 *
	 * @param int $newsletterId Newsletter ID.
	 *
	 * @return void
	 */
	public function refreshQueueCounters(int $newsletterId): void
	{
		$query = $this->db->getQuery(true)
			->select([
				'COUNT(*) AS total',
				"SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent_count",
				"SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count",
				"SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) AS open_count",
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
		$status = $openCount > 0
			? self::STATUS_SENDING
			: ($failedCount > 0 ? self::STATUS_SENT_WITH_FAILURES : self::STATUS_SENT);
		$sentAt = $openCount === 0 ? (new Date('now', 'UTC'))->toSql() : null;
		$update = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_newsletters'))
			->set($this->db->quoteName('recipient_count') . ' = :total')
			->set($this->db->quoteName('sent_count') . ' = :sent')
			->set($this->db->quoteName('failed_count') . ' = :failed')
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('sent_at') . ' = ' . ($sentAt === null ? $this->db->quoteName('sent_at') : ':sentAt'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':total', $total, ParameterType::INTEGER)
			->bind(':sent', $sentCount, ParameterType::INTEGER)
			->bind(':failed', $failedCount, ParameterType::INTEGER)
			->bind(':status', $status, ParameterType::INTEGER)
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		if ($sentAt !== null)
		{
			$update->bind(':sentAt', $sentAt);
		}

		$this->db->setQuery($update)->execute();
	}

	/**
	 * Returns the frozen recipient queue for a newsletter.
	 *
	 * @param int $newsletterId Newsletter ID.
	 *
	 * @return array<int,object> Frozen queue recipients.
	 */
	public function getQueueRecipients(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('email'),
				$this->db->quoteName('source'),
				$this->db->quoteName('status'),
				$this->db->quoteName('attempts'),
				$this->db->quoteName('sent_at'),
				$this->db->quoteName('last_error'),
			])
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->order($this->db->quoteName('email_normalized') . ' ASC')
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObjectList();
	}

	/**
	 * Converts an article record into an absolute routed site URL.
	 *
	 * @param int $contentId Article ID.
	 * @param int $categoryId Category ID.
	 *
	 * @return string Absolute site URL.
	 */
	public function articleUrl(int $contentId, int $categoryId): string
	{
		$link = 'index.php?option=com_content&view=article&id=' . $contentId . '&catid=' . $categoryId;

		return Route::link('site', $link, false, Route::TLS_IGNORE, true);
	}

	/**
	 * Replaces selected content rows.
	 *
	 * @param int $newsletterId Newsletter ID.
	 * @param array<int,array<string,mixed>> $items Selected items.
	 *
	 * @return void
	 */
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
			$contentId = (int) ($item['content_id'] ?? 0);

			if ($contentId <= 0)
			{
				continue;
			}

			$row = (object) [
				'newsletter_id' => $newsletterId,
				'content_id' => $contentId,
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

	/**
	 * Replaces selected Joomla user groups.
	 *
	 * @param int $newsletterId Newsletter ID.
	 * @param array<int,int> $groupIds Group IDs.
	 *
	 * @return void
	 */
	private function replaceGroups(int $newsletterId, array $groupIds): void
	{
		$delete = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_newsletter_groups'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$this->db->setQuery($delete)->execute();

		foreach (array_values(array_unique(array_filter(array_map('intval', $groupIds)))) as $groupId)
		{
			$row = (object) [
				'newsletter_id' => $newsletterId,
				'group_id' => $groupId,
			];
			$this->db->insertObject('#__pungamail_newsletter_groups', $row);
		}
	}
}
