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

/** Persistence service for recurring digest definitions and execution history. */
final class DigestRepository
{
	/** @param DatabaseInterface $db Database connection. */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/** @return object|null */
	public function find(int $id): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_digests'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/** @return array<int,object> */
	public function due(int $limit = 20): array
	{
		$state = 1;
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_digests'))
			->where($this->db->quoteName('state') . ' = :state')
			->where($this->db->quoteName('next_run_at') . ' <= :now')
			->order($this->db->quoteName('next_run_at') . ' ASC')
			->bind(':state', $state, ParameterType::INTEGER)
			->bind(':now', $now);

		return $this->db->setQuery($query, 0, max(1, $limit))->loadObjectList();
	}

	/**
	 * Acquires a connection-scoped lock for one digest generation.
	 *
	 * @param int $digestId Digest ID.
	 *
	 * @return bool True when this worker owns the digest run.
	 */
	public function acquireRunLock(int $digestId): bool
	{
		$name = 'pungamail.digest.' . $digestId;
		$query = $this->db->getQuery(true)
			->select('GET_LOCK(:lockName, 0)')
			->bind(':lockName', $name);

		return (int) $this->db->setQuery($query)->loadResult() === 1;
	}

	/** @return void */
	public function releaseRunLock(int $digestId): void
	{
		$name = 'pungamail.digest.' . $digestId;
		$query = $this->db->getQuery(true)
			->select('RELEASE_LOCK(:lockName)')
			->bind(':lockName', $name);
		$this->db->setQuery($query)->loadResult();
	}

	/** @return int */
	public function save(int $id, array $data, int $userId): int
	{
		$title = trim((string) ($data['title'] ?? ''));
		$templateId = (int) ($data['template_id'] ?? 0);

		if ($title === '' || $templateId <= 0)
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_DIGEST_REQUIRED'));
		}

		$generationMode = in_array((string) ($data['generation_mode'] ?? 'draft'), ['draft', 'auto'], true) ? (string) $data['generation_mode'] : 'draft';
		$cutoffMode = in_array((string) ($data['cutoff_mode'] ?? 'since_last'), ['since_last', 'rolling'], true) ? (string) $data['cutoff_mode'] : 'since_last';
		$emptyAction = in_array((string) ($data['empty_action'] ?? 'skip'), ['skip', 'create_draft'], true) ? (string) $data['empty_action'] : 'skip';
		$now = (new Date('now', 'UTC'))->toSql();
		$nextRun = trim((string) ($data['next_run_at'] ?? '')) ?: $now;
		$values = [
			'title' => $title,
			'template_id' => $templateId,
			'subject_pattern' => trim((string) ($data['subject_pattern'] ?? '')),
			'recurrence_minutes' => max(15, min(525600, (int) ($data['recurrence_minutes'] ?? 10080))),
			'next_run_at' => $nextRun,
			'cutoff_mode' => $cutoffMode,
			'rolling_hours' => max(1, min(8760, (int) ($data['rolling_hours'] ?? 168))),
			'include_subscribers' => (int) ($data['include_subscribers'] ?? 0) === 1 ? 1 : 0,
			'generation_mode' => $generationMode,
			'empty_action' => $emptyAction,
		];
		$this->db->transactionStart();

		try
		{
			if ($id <= 0)
			{
				$row = (object) ($values + [
					'state' => (int) ($data['state'] ?? 1) === 1 ? 1 : 0,
					'last_cutoff_at' => null,
					'last_run_at' => null,
					'created' => $now,
					'modified' => $now,
					'created_by' => $userId,
				]);
				$this->db->insertObject('#__pungamail_digests', $row, 'id');
				$id = (int) $row->id;
			}
			else
			{
				$subjectPattern = (string) $values['subject_pattern'];
				$recurrence = (int) $values['recurrence_minutes'];
				$cutoff = (string) $values['cutoff_mode'];
				$rollingHours = (int) $values['rolling_hours'];
				$includeSubscribers = (int) $values['include_subscribers'];
				$mode = (string) $values['generation_mode'];
				$empty = (string) $values['empty_action'];
				$update = $this->db->getQuery(true)
					->update($this->db->quoteName('#__pungamail_digests'))
					->set($this->db->quoteName('title') . ' = :title')
					->set($this->db->quoteName('template_id') . ' = :templateId')
					->set($this->db->quoteName('subject_pattern') . ' = :subjectPattern')
					->set($this->db->quoteName('recurrence_minutes') . ' = :recurrence')
					->set($this->db->quoteName('next_run_at') . ' = :nextRun')
					->set($this->db->quoteName('cutoff_mode') . ' = :cutoffMode')
					->set($this->db->quoteName('rolling_hours') . ' = :rollingHours')
					->set($this->db->quoteName('include_subscribers') . ' = :includeSubscribers')
					->set($this->db->quoteName('generation_mode') . ' = :generationMode')
					->set($this->db->quoteName('empty_action') . ' = :emptyAction')
					->set($this->db->quoteName('modified') . ' = :modified')
					->where($this->db->quoteName('id') . ' = :id')
					->bind(':title', $title)
					->bind(':templateId', $templateId, ParameterType::INTEGER)
					->bind(':subjectPattern', $subjectPattern)
					->bind(':recurrence', $recurrence, ParameterType::INTEGER)
					->bind(':nextRun', $nextRun)
					->bind(':cutoffMode', $cutoff)
					->bind(':rollingHours', $rollingHours, ParameterType::INTEGER)
					->bind(':includeSubscribers', $includeSubscribers, ParameterType::INTEGER)
					->bind(':generationMode', $mode)
					->bind(':emptyAction', $empty)
					->bind(':modified', $now)
					->bind(':id', $id, ParameterType::INTEGER);
				$this->db->setQuery($update)->execute();
			}

			$this->replaceSimple('#__pungamail_digest_sources', $id, 'source_key', (array) ($data['source_keys'] ?? []), false);
			$this->replaceSimple('#__pungamail_digest_topics', $id, 'topic_id', (array) ($data['topic_ids'] ?? []), true);
			$this->replaceSimple('#__pungamail_digest_groups', $id, 'group_id', (array) ($data['group_ids'] ?? []), true);
			$this->replaceCategories($id, (array) ($data['categories'] ?? []));
			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}

		return $id;
	}

	/** @return array<int,string> */
	public function getSourceKeys(int $digestId): array
	{
		return array_map('strval', $this->getRelationColumn('#__pungamail_digest_sources', $digestId, 'source_key'));
	}

	/** @return array<int,int> */
	public function getTopicIds(int $digestId): array
	{
		return array_map('intval', $this->getRelationColumn('#__pungamail_digest_topics', $digestId, 'topic_id'));
	}

	/** @return array<int,int> */
	public function getGroupIds(int $digestId): array
	{
		return array_map('intval', $this->getRelationColumn('#__pungamail_digest_groups', $digestId, 'group_id'));
	}

	/** @return array<string,array<int,int>> */
	public function getCategories(int $digestId): array
	{
		$query = $this->db->getQuery(true)
			->select(['source_key', 'category_id'])
			->from($this->db->quoteName('#__pungamail_digest_categories'))
			->where($this->db->quoteName('digest_id') . ' = :id')
			->bind(':id', $digestId, ParameterType::INTEGER);
		$result = [];

		foreach ($this->db->setQuery($query)->loadObjectList() as $row)
		{
			$result[(string) $row->source_key][] = (int) $row->category_id;
		}

		return $result;
	}

	/** @return array<int,object> */
	public function getRuns(int $digestId, int $limit = 50): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_digest_runs'))
			->where($this->db->quoteName('digest_id') . ' = :id')
			->order($this->db->quoteName('started_at') . ' DESC')
			->bind(':id', $digestId, ParameterType::INTEGER);

		return $this->db->setQuery($query, 0, max(1, $limit))->loadObjectList();
	}

	/** @return int */
	public function startRun(int $digestId): int
	{
		$row = (object) [
			'digest_id' => $digestId,
			'started_at' => (new Date('now', 'UTC'))->toSql(),
			'completed_at' => null,
			'status' => 'running',
			'newsletter_id' => null,
			'item_count' => 0,
			'message' => null,
		];
		$this->db->insertObject('#__pungamail_digest_runs', $row, 'id');

		return (int) $row->id;
	}

	/** @return void */
	public function finishRun(int $digestId, int $runId, string $status, ?int $newsletterId, int $itemCount, string $message, bool $advanceCutoff): void
	{
		$digest = $this->find($digestId);

		if ($digest === null)
		{
			return;
		}

		$now = (new Date('now', 'UTC'))->toSql();
		$next = new Date((string) $digest->next_run_at, 'UTC');
		$minutes = max(15, (int) $digest->recurrence_minutes);

		do
		{
			$next->modify('+' . $minutes . ' minutes');
		}
		while ($next->toSql() <= $now);

		$this->db->transactionStart();

		try
		{
			$runStatus = mb_substr($status, 0, 20);
			$runMessage = trim($message) !== '' ? mb_substr($message, 0, 4000, 'UTF-8') : null;
			$updateRun = $this->db->getQuery(true)
				->update($this->db->quoteName('#__pungamail_digest_runs'))
				->set($this->db->quoteName('completed_at') . ' = :completedAt')
				->set($this->db->quoteName('status') . ' = :status')
				->set($this->db->quoteName('newsletter_id') . ($newsletterId === null ? ' = NULL' : ' = :newsletterId'))
				->set($this->db->quoteName('item_count') . ' = :itemCount')
				->set($this->db->quoteName('message') . ($runMessage === null ? ' = NULL' : ' = :message'))
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':completedAt', $now)
				->bind(':status', $runStatus)
				->bind(':itemCount', $itemCount, ParameterType::INTEGER)
				->bind(':id', $runId, ParameterType::INTEGER);

			if ($newsletterId !== null)
			{
				$updateRun->bind(':newsletterId', $newsletterId, ParameterType::INTEGER);
			}

			if ($runMessage !== null)
			{
				$updateRun->bind(':message', $runMessage);
			}

			$this->db->setQuery($updateRun)->execute();
			$nextRunAt = $next->toSql();
			$updateDigest = $this->db->getQuery(true)
				->update($this->db->quoteName('#__pungamail_digests'))
				->set($this->db->quoteName('last_run_at') . ' = :lastRunAt')
				->set($this->db->quoteName('last_cutoff_at') . ($advanceCutoff ? ' = :lastCutoffAt' : ' = ' . $this->db->quoteName('last_cutoff_at')))
				->set($this->db->quoteName('next_run_at') . ' = :nextRunAt')
				->set($this->db->quoteName('modified') . ' = :modified')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':lastRunAt', $now)
				->bind(':nextRunAt', $nextRunAt)
				->bind(':modified', $now)
				->bind(':id', $digestId, ParameterType::INTEGER);

			if ($advanceCutoff)
			{
				$updateDigest->bind(':lastCutoffAt', $now);
			}

			$this->db->setQuery($updateDigest)->execute();
			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}
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
			->update($this->db->quoteName('#__pungamail_digests'))
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

		$trashed = -2;
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__pungamail_digests'))
			->whereIn($this->db->quoteName('id'), $ids)
			->where($this->db->quoteName('state') . ' = :state')
			->bind(':state', $trashed, ParameterType::INTEGER);
		$deleteIds = array_map('intval', $this->db->setQuery($query)->loadColumn());

		if ($deleteIds === [])
		{
			return 0;
		}

		foreach (['#__pungamail_digest_sources', '#__pungamail_digest_categories', '#__pungamail_digest_topics', '#__pungamail_digest_groups', '#__pungamail_digest_runs'] as $table)
		{
			$delete = $this->db->getQuery(true)->delete($this->db->quoteName($table))->whereIn($this->db->quoteName('digest_id'), $deleteIds);
			$this->db->setQuery($delete)->execute();
		}

		$delete = $this->db->getQuery(true)->delete($this->db->quoteName('#__pungamail_digests'))->whereIn($this->db->quoteName('id'), $deleteIds);
		$this->db->setQuery($delete)->execute();

		return $this->db->getAffectedRows();
	}

	/** @return array<int,mixed> */
	private function getRelationColumn(string $table, int $digestId, string $column): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName($column))
			->from($this->db->quoteName($table))
			->where($this->db->quoteName('digest_id') . ' = :id')
			->bind(':id', $digestId, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadColumn();
	}

	/** @return void */
	private function replaceSimple(string $table, int $digestId, string $column, array $values, bool $integers): void
	{
		$delete = $this->db->getQuery(true)
			->delete($this->db->quoteName($table))
			->where($this->db->quoteName('digest_id') . ' = :id')
			->bind(':id', $digestId, ParameterType::INTEGER);
		$this->db->setQuery($delete)->execute();
		$values = $integers ? array_map('intval', $values) : array_map('strval', $values);

		foreach (array_values(array_unique(array_filter($values))) as $value)
		{
			$row = (object) ['digest_id' => $digestId, $column => $value];
			$this->db->insertObject($table, $row);
		}
	}

	/** @return void */
	private function replaceCategories(int $digestId, array $categories): void
	{
		$delete = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_digest_categories'))
			->where($this->db->quoteName('digest_id') . ' = :id')
			->bind(':id', $digestId, ParameterType::INTEGER);
		$this->db->setQuery($delete)->execute();

		foreach ($categories as $sourceKey => $ids)
		{
			foreach (array_values(array_unique(array_filter(array_map('intval', (array) $ids)))) as $categoryId)
			{
				$row = (object) ['digest_id' => $digestId, 'source_key' => (string) $sourceKey, 'category_id' => $categoryId];
				$this->db->insertObject('#__pungamail_digest_categories', $row);
			}
		}
	}
}
