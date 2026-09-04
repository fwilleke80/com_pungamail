<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;

/** Resolves, explains, deduplicates and suppresses newsletter recipients. */
final class RecipientResolver
{
	/**
	 * @param DatabaseInterface     $db          Database connection.
	 * @param SubscriberRepository $subscribers Subscriber repository.
	 */
	public function __construct(
		private readonly DatabaseInterface $db,
		private readonly SubscriberRepository $subscribers
	)
	{
	}

	/**
	 * Resolves final recipients without changing subscription state.
	 *
	 * @return array<int,array{subscriber_id:int,user_id:?int,email:string,email_normalized:string,recipient_name:string,source:string}>
	 */
	public function resolve(object $newsletter, array $groupIds, array $topicIds = []): array
	{
		return $this->resolveWithReport($newsletter, $groupIds, $topicIds, false)['recipients'];
	}

	/**
	 * Resolves recipients and exclusions. Canonical rows are created only during
	 * actual queue construction, never during preflight inspection.
	 *
	 * @return array{recipients:array<int,array<string,mixed>>,excluded:array<int,array<string,mixed>>,counts:array<string,int>}
	 */
	public function resolveWithReport(object $newsletter, array $groupIds, array $topicIds = [], bool $canonicalize = false): array
	{
		$groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds))));
		$topicIds = array_values(array_unique(array_filter(array_map('intval', $topicIds))));
		$recipients = [];
		$excluded = [];
		$counts = ['intended' => 0, 'suppressed' => 0, 'unsubscribed' => 0, 'not_in_topic' => 0, 'invalid' => 0, 'deduplicated' => 0];

		$includeAllSubscribers = (int) $newsletter->include_subscribers === 1;

		if ($includeAllSubscribers || $topicIds !== [])
		{
			$query = $this->db->getQuery(true)
				->select(['s.*', 'u.name AS user_name', 'x.reason AS suppression_reason'])
				->from($this->db->quoteName('#__pungamail_subscribers', 's'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON u.id = s.user_id')
				->leftJoin($this->db->quoteName('#__pungamail_suppressions', 'x') . ' ON x.email_normalized = s.email_normalized');
			$rows = $this->db->setQuery($query)->loadObjectList();
			$topicMembers = $topicIds !== [] ? $this->topicMemberMap($topicIds) : [];

			foreach ($rows as $row)
			{
				$counts['intended']++;

				if (!$includeAllSubscribers && $topicIds !== [] && !isset($topicMembers[(int) $row->id]))
				{
					$this->exclude($excluded, $counts, $row, 'subscriber', 'not_in_selected_topic', 'not_in_topic');
					continue;
				}

				if (!$this->validateCandidate($row, 'subscriber', $excluded, $counts))
				{
					continue;
				}

				$this->addRecipient($recipients, $excluded, $counts, [
					'subscriber_id' => (int) $row->id,
					'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
					'email' => (string) $row->email,
					'email_normalized' => (string) $row->email_normalized,
					'recipient_name' => RecipientName::resolve((string) ($row->user_name ?? $row->recipient_name ?? ''), (string) $row->email),
					'source' => 'subscriber',
				]);
			}
		}

		if ($groupIds !== [])
		{
			$defaultSubscribed = (bool) ComponentHelper::getParams('com_pungamail')->get('default_user_subscribed', 0);
			$query = $this->db->getQuery(true)
				->select(['DISTINCT u.id', 'u.name', 'u.email'])
				->from($this->db->quoteName('#__users', 'u'))
				->innerJoin($this->db->quoteName('#__user_usergroup_map', 'm') . ' ON m.user_id = u.id')
				->innerJoin($this->db->quoteName('#__usergroups', 'member_group') . ' ON member_group.id = m.group_id')
				->innerJoin($this->db->quoteName('#__usergroups', 'target_group') . ' ON member_group.lft BETWEEN target_group.lft AND target_group.rgt')
				->where($this->db->quoteName('u.block') . ' = 0')
				->whereIn($this->db->quoteName('target_group.id'), $groupIds);

			foreach ($this->db->setQuery($query)->loadObjectList() as $user)
			{
				$counts['intended']++;
				$email = trim((string) $user->email);

				if (!filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					$this->excludeRaw($excluded, $counts, $email, 'user-group', 'invalid_address', 'invalid');
					continue;
				}

				$normalized = Address::normalize($email);
				$subscriber = $this->subscribers->findByUserId((int) $user->id) ?? $this->subscribers->findByEmail($email);

				if ($this->subscribers->isSuppressed($normalized))
				{
					$this->excludeRaw($excluded, $counts, $email, 'user-group', 'suppressed', 'suppressed');
					continue;
				}

				if ($subscriber !== null && (int) $subscriber->status !== SubscriberRepository::STATUS_SUBSCRIBED)
				{
					$this->excludeRaw($excluded, $counts, $email, 'user-group', 'unsubscribed', 'unsubscribed');
					continue;
				}

				if ($subscriber === null && !$defaultSubscribed)
				{
					$this->excludeRaw($excluded, $counts, $email, 'user-group', 'not_subscribed', 'unsubscribed');
					continue;
				}

				if ($subscriber === null && $canonicalize)
				{
					$subscriber = $this->subscribers->ensureRecipient((int) $user->id, $email);
				}

				$this->addRecipient($recipients, $excluded, $counts, [
					'subscriber_id' => $subscriber !== null ? (int) $subscriber->id : 0,
					'user_id' => (int) $user->id,
					'email' => $email,
					'email_normalized' => $normalized,
					'recipient_name' => RecipientName::resolve((string) $user->name, $email),
					'source' => 'user-group',
				]);
			}
		}

		ksort($recipients, SORT_STRING);

		return ['recipients' => array_values($recipients), 'excluded' => $excluded, 'counts' => $counts];
	}

	/** @return array<int,bool> */
	private function topicMemberMap(array $topicIds): array
	{
		$status = TopicRepository::MEMBERSHIP_SUBSCRIBED;
		$everyone = $this->db->quote(TopicRepository::AUDIENCE_EVERYONE);
		$registered = $this->db->quote(TopicRepository::AUDIENCE_REGISTERED);
		$groups = $this->db->quote(TopicRepository::AUDIENCE_GROUPS);
		$query = $this->db->getQuery(true)
			->select('DISTINCT ' . $this->db->quoteName('st.subscriber_id'))
			->from($this->db->quoteName('#__pungamail_subscriber_topics', 'st'))
			->innerJoin($this->db->quoteName('#__pungamail_subscribers', 's') . ' ON s.id = st.subscriber_id')
			->innerJoin($this->db->quoteName('#__pungamail_topics', 't') . ' ON t.id = st.topic_id AND t.state = 1')
			->leftJoin($this->db->quoteName('#__pungamail_topic_groups', 'tg') . ' ON tg.topic_id = t.id')
			->leftJoin($this->db->quoteName('#__user_usergroup_map', 'm') . ' ON m.user_id = s.user_id')
			->leftJoin($this->db->quoteName('#__usergroups', 'member_group') . ' ON member_group.id = m.group_id')
			->leftJoin($this->db->quoteName('#__usergroups', 'target_group') . ' ON member_group.lft BETWEEN target_group.lft AND target_group.rgt')
			->whereIn($this->db->quoteName('st.topic_id'), $topicIds)
			->where($this->db->quoteName('st.status') . ' = :status')
			->where('(t.audience_mode = ' . $everyone
				. ' OR (t.audience_mode = ' . $registered . ' AND s.user_id IS NOT NULL)'
				. ' OR (t.audience_mode = ' . $groups . ' AND s.user_id IS NOT NULL AND target_group.id = tg.group_id))')
			->bind(':status', $status, \Joomla\Database\ParameterType::INTEGER);

		return array_fill_keys(array_map('intval', $this->db->setQuery($query)->loadColumn()), true);
	}

	/** @return bool */
	private function validateCandidate(object $row, string $source, array &$excluded, array &$counts): bool
	{
		if (!filter_var((string) $row->email, FILTER_VALIDATE_EMAIL))
		{
			$this->exclude($excluded, $counts, $row, $source, 'invalid_address', 'invalid');
			return false;
		}

		if ((int) $row->status !== SubscriberRepository::STATUS_SUBSCRIBED)
		{
			$this->exclude($excluded, $counts, $row, $source, 'unsubscribed', 'unsubscribed');
			return false;
		}

		if ((string) ($row->suppression_reason ?? '') !== '')
		{
			$reason = (string) $row->suppression_reason;
			$displayReason = $reason === 'hard-bounce' ? 'hard_bounced' : ($reason === 'soft-bounce-threshold' ? 'soft_bounce_threshold' : 'suppressed');
			$this->exclude($excluded, $counts, $row, $source, $displayReason, 'suppressed');
			return false;
		}

		return true;
	}

	/** @return void */
	private function addRecipient(array &$recipients, array &$excluded, array &$counts, array $recipient): void
	{
		$normalized = (string) $recipient['email_normalized'];

		if (isset($recipients[$normalized]))
		{
			$this->excludeRaw($excluded, $counts, (string) $recipient['email'], (string) $recipient['source'], 'duplicate_eliminated', 'deduplicated');
			return;
		}

		$recipients[$normalized] = $recipient;
	}

	/** @return void */
	private function exclude(array &$excluded, array &$counts, object $row, string $source, string $reason, string $counter): void
	{
		$this->excludeRaw($excluded, $counts, (string) $row->email, $source, $reason, $counter);
	}

	/** @return void */
	private function excludeRaw(array &$excluded, array &$counts, string $email, string $source, string $reason, string $counter): void
	{
		$excluded[] = ['email' => $email, 'source' => $source, 'reason' => $reason];
		$counts[$counter] = ($counts[$counter] ?? 0) + 1;
	}
}
