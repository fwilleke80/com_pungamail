<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Persists public mailing topics, memberships and confirmed preference changes.
 */
final class TopicRepository
{
	public const MEMBERSHIP_PENDING = 0;
	public const MEMBERSHIP_SUBSCRIBED = 1;
	public const MEMBERSHIP_UNSUBSCRIBED = 2;
	public const AUDIENCE_EVERYONE = 'everyone';
	public const AUDIENCE_REGISTERED = 'registered';
	public const AUDIENCE_GROUPS = 'groups';

	/** @param DatabaseInterface $db Database connection. */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/** @return object|null */
	public function find(int $id): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_topics'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/**
	 * Returns every non-trashed channel for administrator membership editing.
	 *
	 * @return array<int,object>
	 */
	public function availableForAdministration(): array
	{
		$trashed = -2;
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_topics'))
			->where($this->db->quoteName('state') . ' <> :trashed')
			->order($this->db->quoteName('ordering') . ' ASC')
			->order($this->db->quoteName('title') . ' ASC')
			->bind(':trashed', $trashed, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObjectList();
	}

	/** @return array<int,object> */
	public function active(?array $onlyIds = null): array
	{
		$state = 1;
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_topics'))
			->where($this->db->quoteName('state') . ' = :state')
			->order($this->db->quoteName('ordering') . ' ASC')
			->order($this->db->quoteName('title') . ' ASC')
			->bind(':state', $state, ParameterType::INTEGER);

		if (is_array($onlyIds))
		{
			$ids = array_values(array_unique(array_filter(array_map('intval', $onlyIds))));

			if ($ids === [])
			{
				return [];
			}

			$query->whereIn($this->db->quoteName('id'), $ids);
		}

		return $this->db->setQuery($query)->loadObjectList();
	}

	/** @return int */
	public function save(int $id, string $title, string $alias, string $description, int $userId, string $audienceMode = self::AUDIENCE_EVERYONE, array $groupIds = []): int
	{
		$title = trim($title);

		if ($title === '')
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_TOPIC_TITLE_REQUIRED'));
		}

		$alias = trim($alias) !== '' ? OutputFilter::stringURLSafe($alias) : OutputFilter::stringURLSafe($title);

		if ($alias === '')
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_TOPIC_ALIAS_REQUIRED'));
		}

		$duplicateId = $id;
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__pungamail_topics'))
			->where($this->db->quoteName('alias') . ' = :alias')
			->where($this->db->quoteName('id') . ' <> :id')
			->bind(':alias', $alias)
			->bind(':id', $duplicateId, ParameterType::INTEGER);

		if ((int) $this->db->setQuery($query)->loadResult() > 0)
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_TOPIC_ALIAS_EXISTS'));
		}

		$audienceMode = $this->normalizeAudienceMode($audienceMode);
		$groupIds = $audienceMode === self::AUDIENCE_GROUPS ? $this->normalizeGroupIds($groupIds) : [];
		$now = (new Date('now', 'UTC'))->toSql();

		if ($id <= 0)
		{
			$row = (object) [
				'title' => $title,
				'alias' => $alias,
				'description' => trim($description) !== '' ? trim($description) : null,
				'audience_mode' => $audienceMode,
				'state' => 1,
				'ordering' => $this->nextOrdering(),
				'created' => $now,
				'modified' => $now,
				'created_by' => $userId,
			];
			$this->db->insertObject('#__pungamail_topics', $row, 'id');
			$this->replaceTopicGroups((int) $row->id, $groupIds);

			return (int) $row->id;
		}

		$descriptionValue = trim($description) !== '' ? trim($description) : null;
		$update = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_topics'))
			->set($this->db->quoteName('title') . ' = :title')
			->set($this->db->quoteName('alias') . ' = :alias')
			->set($this->db->quoteName('description') . ($descriptionValue === null ? ' = NULL' : ' = :description'))
			->set($this->db->quoteName('audience_mode') . ' = :audienceMode')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':title', $title)
			->bind(':alias', $alias)
			->bind(':audienceMode', $audienceMode)
			->bind(':modified', $now)
			->bind(':id', $id, ParameterType::INTEGER);

		if ($descriptionValue !== null)
		{
			$update->bind(':description', $descriptionValue);
		}

		$this->db->setQuery($update)->execute();
		$this->replaceTopicGroups($id, $groupIds);

		return $id;
	}

	/**
	 * Persists Joomla drag-and-drop ordering values.
	 *
	 * @param array<int,int> $ids Item IDs in the submitted table order.
	 * @param array<int,int> $orderings Ordering values paired with the IDs.
	 *
	 * @return void
	 */
	public function saveOrdering(array $ids, array $orderings): void
	{
		if (count($ids) !== count($orderings))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_CHANNEL_ORDER'));
		}

		$this->db->transactionStart();

		try
		{
			foreach ($ids as $index => $id)
			{
				$id = (int) $id;
				$ordering = (int) ($orderings[$index] ?? 0);

				if ($id <= 0)
				{
					continue;
				}

				$query = $this->db->getQuery(true)
					->update($this->db->quoteName('#__pungamail_topics'))
					->set($this->db->quoteName('ordering') . ' = :ordering')
					->where($this->db->quoteName('id') . ' = :id')
					->bind(':ordering', $ordering, ParameterType::INTEGER)
					->bind(':id', $id, ParameterType::INTEGER);
				$this->db->setQuery($query)->execute();
			}

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
			->update($this->db->quoteName('#__pungamail_topics'))
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

		foreach (['#__pungamail_subscriber_topics', '#__pungamail_newsletter_topics', '#__pungamail_digest_topics'] as $table)
		{
			$query = $this->db->getQuery(true)
				->select('COUNT(*)')
				->from($this->db->quoteName($table))
				->whereIn($this->db->quoteName('topic_id'), $ids);

			if ((int) $this->db->setQuery($query)->loadResult() > 0)
			{
				throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_TOPIC_IN_USE'));
			}
		}

		$deleteGroups = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_topic_groups'))
			->whereIn($this->db->quoteName('topic_id'), $ids);
		$this->db->setQuery($deleteGroups)->execute();

		$trashed = -2;
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_topics'))
			->whereIn($this->db->quoteName('id'), $ids)
			->where($this->db->quoteName('state') . ' = :state')
			->bind(':state', $trashed, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();

		return $this->db->getAffectedRows();
	}

	/** @return array<int,int> */
	public function getSubscriberTopicIds(int $subscriberId, int $status = self::MEMBERSHIP_SUBSCRIBED): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('topic_id'))
			->from($this->db->quoteName('#__pungamail_subscriber_topics'))
			->where($this->db->quoteName('subscriber_id') . ' = :subscriberId')
			->where($this->db->quoteName('status') . ' = :status')
			->bind(':subscriberId', $subscriberId, ParameterType::INTEGER)
			->bind(':status', $status, ParameterType::INTEGER);

		return array_map('intval', $this->db->setQuery($query)->loadColumn());
	}

	/** @return void */
	public function stageInitialTopics(int $subscriberId, array $visibleIds, array $selectedIds): void
	{
		$visible = $this->validActiveIds($visibleIds);
		$selected = array_flip($this->validActiveIds($selectedIds));

		foreach ($visible as $topicId)
		{
			$this->upsertMembership(
				$subscriberId,
				$topicId,
				isset($selected[$topicId]) ? self::MEMBERSHIP_PENDING : self::MEMBERSHIP_UNSUBSCRIBED
			);
		}
	}

	/** @return void */
	public function activatePendingTopics(int $subscriberId): void
	{
		$pending = self::MEMBERSHIP_PENDING;
		$active = self::MEMBERSHIP_SUBSCRIBED;
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_subscriber_topics'))
			->set($this->db->quoteName('status') . ' = :active')
			->set($this->db->quoteName('confirmed_at') . ' = :now')
			->set($this->db->quoteName('unsubscribed_at') . ' = NULL')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('subscriber_id') . ' = :subscriberId')
			->where($this->db->quoteName('status') . ' = :pending')
			->bind(':active', $active, ParameterType::INTEGER)
			->bind(':now', $now)
			->bind(':modified', $now)
			->bind(':subscriberId', $subscriberId, ParameterType::INTEGER)
			->bind(':pending', $pending, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}

	/**
	 * Changes only topics exposed by the current module, leaving all others intact.
	 *
	 * @return void
	 */
	public function updateVisibleTopics(int $subscriberId, array $visibleIds, array $selectedIds): void
	{
		$visible = $this->validActiveIds($visibleIds);
		$selected = array_flip($this->validActiveIds($selectedIds));

		foreach ($visible as $topicId)
		{
			$status = isset($selected[$topicId]) ? self::MEMBERSHIP_SUBSCRIBED : self::MEMBERSHIP_UNSUBSCRIBED;
			$this->upsertMembership($subscriberId, $topicId, $status);
		}
	}

	/**
	 * Replaces memberships for every non-trashed channel exposed in the administrator.
	 * Unpublished channels may keep memberships so they resume naturally when republished.
	 *
	 * @return void
	 */
	public function updateAdministratorTopics(int $subscriberId, array $visibleIds, array $selectedIds, ?int $userId = null): void
	{
		$visible = $this->validAdministrativeIds($visibleIds);
		$eligible = array_flip($this->eligibleIds($visible, $userId, false));
		$selected = array_flip($this->eligibleIds($selectedIds, $userId, false));

		foreach ($visible as $topicId)
		{
			if (!isset($eligible[$topicId]))
			{
				continue;
			}

			$status = isset($selected[$topicId]) ? self::MEMBERSHIP_SUBSCRIBED : self::MEMBERSHIP_UNSUBSCRIBED;
			$this->upsertMembership($subscriberId, $topicId, $status);
		}
	}

	/** Adds active topic memberships without removing existing memberships. */
	public function subscribeTopics(int $subscriberId, array $topicIds, ?int $userId = null): void
	{
		foreach ($this->eligibleIds($topicIds, $userId, true) as $topicId)
		{
			$this->upsertMembership($subscriberId, $topicId, self::MEMBERSHIP_SUBSCRIBED);
		}
	}

	/**
	 * Stores a double-opt-in preference request for an email-only subscriber.
	 *
	 * @return int Request ID.
	 */
	public function createPreferenceRequest(int $subscriberId, array $visibleIds, array $selectedIds, string $tokenHash, string $expiresAt): int
	{
		$visible = $this->validActiveIds($visibleIds);
		$selected = array_flip($this->validActiveIds($selectedIds));
		$row = (object) [
			'subscriber_id' => $subscriberId,
			'token_hash' => $tokenHash,
			'expires_at' => $expiresAt,
			'created' => (new Date('now', 'UTC'))->toSql(),
		];
		$this->db->insertObject('#__pungamail_preference_requests', $row, 'id');
		$modified = (string) $row->created;
		$updateSubscriber = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_subscribers'))
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :subscriberId')
			->bind(':modified', $modified)
			->bind(':subscriberId', $subscriberId, ParameterType::INTEGER);
		$this->db->setQuery($updateSubscriber)->execute();

		foreach ($visible as $topicId)
		{
			$item = (object) [
				'request_id' => (int) $row->id,
				'topic_id' => $topicId,
				'action' => isset($selected[$topicId]) ? 'subscribe' : 'unsubscribe',
			];
			$this->db->insertObject('#__pungamail_preference_request_topics', $item);
		}

		return (int) $row->id;
	}

	/** @return object|null */
	public function findPreferenceRequest(string $tokenHash): ?object
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->select(['r.*', 's.email', 's.user_id'])
			->from($this->db->quoteName('#__pungamail_preference_requests', 'r'))
			->innerJoin($this->db->quoteName('#__pungamail_subscribers', 's') . ' ON s.id = r.subscriber_id')
			->where($this->db->quoteName('r.token_hash') . ' = :hash')
			->where($this->db->quoteName('r.expires_at') . ' >= :now')
			->bind(':hash', $tokenHash)
			->bind(':now', $now);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/** @return bool */
	public function confirmPreferenceRequest(string $tokenHash): bool
	{
		$request = $this->findPreferenceRequest($tokenHash);

		if ($request === null)
		{
			return false;
		}

		$requestId = (int) $request->id;
		$query = $this->db->getQuery(true)
			->select(['topic_id', 'action'])
			->from($this->db->quoteName('#__pungamail_preference_request_topics'))
			->where($this->db->quoteName('request_id') . ' = :requestId')
			->bind(':requestId', $requestId, ParameterType::INTEGER);

		$this->db->transactionStart();

		try
		{
			foreach ($this->db->setQuery($query)->loadObjectList() as $item)
			{
				$topicId = (int) $item->topic_id;
				$isSubscribe = (string) $item->action === 'subscribe';

				if ($isSubscribe && !in_array($topicId, $this->eligibleIds([$topicId], $request->user_id !== null ? (int) $request->user_id : null, true), true))
				{
					continue;
				}

				$status = $isSubscribe ? self::MEMBERSHIP_SUBSCRIBED : self::MEMBERSHIP_UNSUBSCRIBED;
				$this->upsertMembership((int) $request->subscriber_id, $topicId, $status);
			}

			$this->deletePreferenceRequest($requestId);
			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}

		return true;
	}


	/** @return array<int,int> */
	public function getTopicGroupIds(int $topicId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('group_id'))
			->from($this->db->quoteName('#__pungamail_topic_groups'))
			->where($this->db->quoteName('topic_id') . ' = :topicId')
			->bind(':topicId', $topicId, ParameterType::INTEGER);

		return array_map('intval', $this->db->setQuery($query)->loadColumn());
	}

	/**
	 * Returns published Channels the current account is allowed to subscribe to.
	 * A null user ID represents an email-only/external subscriber.
	 *
	 * @return array<int,object>
	 */
	public function activeForUser(?int $userId, ?array $onlyIds = null): array
	{
		$topics = $this->active($onlyIds);
		$eligible = array_flip($this->eligibleIds(array_map(static fn (object $topic): int => (int) $topic->id, $topics), $userId, true));

		return array_values(array_filter($topics, static fn (object $topic): bool => isset($eligible[(int) $topic->id])));
	}

	/**
	 * Annotates administrator Channel rows with current eligibility for one subscriber.
	 *
	 * @return array<int,object>
	 */
	public function annotateEligibility(array $topics, ?int $userId): array
	{
		$ids = array_map(static fn (object $topic): int => (int) $topic->id, $topics);
		$eligible = array_flip($this->eligibleIds($ids, $userId, false));
		$groupMap = $this->topicGroupMap($ids);
		$groupTitles = $this->groupTitleMap();

		foreach ($topics as $topic)
		{
			$topic->eligible = isset($eligible[(int) $topic->id]);
			$topic->audience_group_ids = $groupMap[(int) $topic->id] ?? [];
			$topic->audience_group_titles = array_values(array_filter(array_map(
				static fn (int $groupId): string => (string) ($groupTitles[$groupId] ?? ''),
				$topic->audience_group_ids
			)));
		}

		return $topics;
	}

	/**
	 * Filters Channel IDs by publication state and current Joomla-account eligibility.
	 *
	 * @return array<int,int>
	 */
	public function eligibleIds(array $ids, ?int $userId, bool $publishedOnly = true): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return [];
		}

		$stateClause = $publishedOnly ? ' = 1' : ' <> -2';
		$query = $this->db->getQuery(true)
			->select(['id', 'audience_mode'])
			->from($this->db->quoteName('#__pungamail_topics'))
			->whereIn($this->db->quoteName('id'), $ids)
			->where($this->db->quoteName('state') . $stateClause);
		$topics = $this->db->setQuery($query)->loadObjectList();
		$userGroups = $userId !== null && $userId > 0 ? array_flip($this->userAuthorisedGroupIds($userId)) : [];
		$groupMap = $this->topicGroupMap($ids);
		$result = [];

		foreach ($topics as $topic)
		{
			$mode = $this->normalizeAudienceMode((string) ($topic->audience_mode ?? self::AUDIENCE_EVERYONE));
			$allowed = $mode === self::AUDIENCE_EVERYONE
				|| ($mode === self::AUDIENCE_REGISTERED && $userId !== null && $userId > 0);

			if ($mode === self::AUDIENCE_GROUPS && $userId !== null && $userId > 0)
			{
				foreach ($groupMap[(int) $topic->id] ?? [] as $groupId)
				{
					if (isset($userGroups[$groupId]))
					{
						$allowed = true;
						break;
					}
				}
			}

			if ($allowed)
			{
				$result[] = (int) $topic->id;
			}
		}

		return $result;
	}

	/** @return array<int,int> */
	public function userAuthorisedGroupIds(int $userId): array
	{
		if ($userId <= 0)
		{
			return [];
		}

		$query = $this->db->getQuery(true)
			->select('DISTINCT ' . $this->db->quoteName('target_group.id'))
			->from($this->db->quoteName('#__user_usergroup_map', 'm'))
			->innerJoin($this->db->quoteName('#__usergroups', 'member_group') . ' ON member_group.id = m.group_id')
			->innerJoin($this->db->quoteName('#__usergroups', 'target_group') . ' ON member_group.lft BETWEEN target_group.lft AND target_group.rgt')
			->where($this->db->quoteName('m.user_id') . ' = :userId')
			->bind(':userId', $userId, ParameterType::INTEGER);

		return array_map('intval', $this->db->setQuery($query)->loadColumn());
	}

	/** @return array<int,array<int,int>> */
	private function topicGroupMap(array $topicIds): array
	{
		$topicIds = array_values(array_unique(array_filter(array_map('intval', $topicIds))));

		if ($topicIds === [])
		{
			return [];
		}

		$query = $this->db->getQuery(true)
			->select(['topic_id', 'group_id'])
			->from($this->db->quoteName('#__pungamail_topic_groups'))
			->whereIn($this->db->quoteName('topic_id'), $topicIds);
		$map = [];

		foreach ($this->db->setQuery($query)->loadObjectList() as $row)
		{
			$map[(int) $row->topic_id][] = (int) $row->group_id;
		}

		return $map;
	}

	/** @return array<int,string> */
	private function groupTitleMap(): array
	{
		$query = $this->db->getQuery(true)
			->select(['id', 'title'])
			->from($this->db->quoteName('#__usergroups'));
		$map = [];

		foreach ($this->db->setQuery($query)->loadObjectList() as $row)
		{
			$map[(int) $row->id] = (string) $row->title;
		}

		return $map;
	}

	/** @return void */
	private function replaceTopicGroups(int $topicId, array $groupIds): void
	{
		$delete = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_topic_groups'))
			->where($this->db->quoteName('topic_id') . ' = :topicId')
			->bind(':topicId', $topicId, ParameterType::INTEGER);
		$this->db->setQuery($delete)->execute();

		foreach ($this->normalizeGroupIds($groupIds) as $groupId)
		{
			$this->db->insertObject('#__pungamail_topic_groups', (object) ['topic_id' => $topicId, 'group_id' => $groupId]);
		}
	}

	/** @return array<int,int> */
	private function normalizeGroupIds(array $groupIds): array
	{
		return array_values(array_unique(array_filter(array_map('intval', $groupIds), static fn (int $id): bool => $id > 0)));
	}

	/** @return string */
	private function normalizeAudienceMode(string $mode): string
	{
		return in_array($mode, [self::AUDIENCE_EVERYONE, self::AUDIENCE_REGISTERED, self::AUDIENCE_GROUPS], true)
			? $mode
			: self::AUDIENCE_EVERYONE;
	}

	/** @return int */
	private function nextOrdering(): int
	{
		$query = $this->db->getQuery(true)
			->select('COALESCE(MAX(' . $this->db->quoteName('ordering') . '), 0) + 1')
			->from($this->db->quoteName('#__pungamail_topics'));

		return max(1, (int) $this->db->setQuery($query)->loadResult());
	}

	/** @return array<int,int> */
	private function validAdministrativeIds(array $ids): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return [];
		}

		$trashed = -2;
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__pungamail_topics'))
			->whereIn($this->db->quoteName('id'), $ids)
			->where($this->db->quoteName('state') . ' <> :trashed')
			->bind(':trashed', $trashed, ParameterType::INTEGER);

		return array_map('intval', $this->db->setQuery($query)->loadColumn());
	}

	/** @return array<int,int> */
	private function validActiveIds(array $ids): array
	{
		return array_map(static fn (object $topic): int => (int) $topic->id, $this->active($ids));
	}

	/** @return void */
	private function upsertMembership(int $subscriberId, int $topicId, int $status): void
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__pungamail_subscriber_topics'))
			->where($this->db->quoteName('subscriber_id') . ' = :subscriberId')
			->where($this->db->quoteName('topic_id') . ' = :topicId')
			->bind(':subscriberId', $subscriberId, ParameterType::INTEGER)
			->bind(':topicId', $topicId, ParameterType::INTEGER);
		$exists = (int) $this->db->setQuery($query)->loadResult() > 0;
		$confirmedAt = $status === self::MEMBERSHIP_SUBSCRIBED ? $now : null;
		$unsubscribedAt = $status === self::MEMBERSHIP_UNSUBSCRIBED ? $now : null;

		if (!$exists)
		{
			$row = (object) [
				'subscriber_id' => $subscriberId,
				'topic_id' => $topicId,
				'status' => $status,
				'confirmed_at' => $confirmedAt,
				'unsubscribed_at' => $unsubscribedAt,
				'modified' => $now,
			];
			$this->db->insertObject('#__pungamail_subscriber_topics', $row);
			return;
		}

		$update = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_subscriber_topics'))
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('confirmed_at') . ($confirmedAt === null ? ' = NULL' : ' = :confirmedAt'))
			->set($this->db->quoteName('unsubscribed_at') . ($unsubscribedAt === null ? ' = NULL' : ' = :unsubscribedAt'))
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('subscriber_id') . ' = :subscriberId')
			->where($this->db->quoteName('topic_id') . ' = :topicId')
			->bind(':status', $status, ParameterType::INTEGER)
			->bind(':modified', $now)
			->bind(':subscriberId', $subscriberId, ParameterType::INTEGER)
			->bind(':topicId', $topicId, ParameterType::INTEGER);

		if ($confirmedAt !== null)
		{
			$update->bind(':confirmedAt', $confirmedAt);
		}

		if ($unsubscribedAt !== null)
		{
			$update->bind(':unsubscribedAt', $unsubscribedAt);
		}

		$this->db->setQuery($update)->execute();
	}

	/** @return void */
	private function deletePreferenceRequest(int $requestId): void
	{
		$deleteItems = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_preference_request_topics'))
			->where($this->db->quoteName('request_id') . ' = :requestId')
			->bind(':requestId', $requestId, ParameterType::INTEGER);
		$this->db->setQuery($deleteItems)->execute();
		$delete = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_preference_requests'))
			->where($this->db->quoteName('id') . ' = :requestId')
			->bind(':requestId', $requestId, ParameterType::INTEGER);
		$this->db->setQuery($delete)->execute();
	}
}
