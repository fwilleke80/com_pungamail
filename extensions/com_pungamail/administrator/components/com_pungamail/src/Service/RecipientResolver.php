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

/**
 * Resolves, deduplicates and suppresses newsletter recipients.
 */
final class RecipientResolver
{
	/**
	 * @param DatabaseInterface    $db          Database connection.
	 * @param SubscriberRepository $subscribers Subscriber repository.
	 */
	public function __construct(
		private readonly DatabaseInterface $db,
		private readonly SubscriberRepository $subscribers
	)
	{
	}

	/**
	 * Resolves final recipients for a newsletter.
	 *
	 * @param object         $newsletter Newsletter row.
	 * @param array<int,int> $groupIds   Selected Joomla group IDs.
	 *
	 * @return array<int,array{subscriber_id:int,user_id:?int,email:string,email_normalized:string,recipient_name:string,source:string}>
	 */
	public function resolve(object $newsletter, array $groupIds): array
	{
		$recipients = [];

		if ((int) $newsletter->include_subscribers === 1)
		{
			$query = $this->db->getQuery(true)
				->select(['s.id', 's.user_id', 's.email', 's.email_normalized', 'u.name AS user_name'])
				->from($this->db->quoteName('#__pungamail_subscribers', 's'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON u.id = s.user_id')
				->leftJoin($this->db->quoteName('#__pungamail_suppressions', 'x') . ' ON x.email_normalized = s.email_normalized')
				->where('s.status = 1')
				->where('x.id IS NULL');

			foreach ($this->db->setQuery($query)->loadObjectList() as $row)
			{
				$recipients[(string) $row->email_normalized] = [
					'subscriber_id' => (int) $row->id,
					'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
					'email' => (string) $row->email,
					'email_normalized' => (string) $row->email_normalized,
					'recipient_name' => RecipientName::resolve((string) ($row->user_name ?? ''), (string) $row->email),
					'source' => 'subscriber',
				];
			}
		}

		if ($groupIds !== [])
		{
			$defaultSubscribed = (bool) ComponentHelper::getParams('com_pungamail')->get('default_user_subscribed', 0);
			$groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds))));
			$query = $this->db->getQuery(true)
				->select(['DISTINCT u.id', 'u.name', 'u.email'])
				->from($this->db->quoteName('#__users', 'u'))
				->innerJoin($this->db->quoteName('#__user_usergroup_map', 'm') . ' ON m.user_id = u.id')
				->innerJoin($this->db->quoteName('#__usergroups', 'member_group') . ' ON member_group.id = m.group_id')
				->innerJoin($this->db->quoteName('#__usergroups', 'target_group') . ' ON member_group.lft BETWEEN target_group.lft AND target_group.rgt')
				->where('u.block = 0')
				->whereIn('target_group.id', $groupIds);

			foreach ($this->db->setQuery($query)->loadObjectList() as $user)
			{
				$email = trim((string) $user->email);

				if (!filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					continue;
				}

				$normalized = Address::normalize($email);

				if (isset($recipients[$normalized]) || $this->subscribers->isSuppressed($normalized))
				{
					continue;
				}

				$subscriber = $this->subscribers->reconcileUserSubscriber((int) $user->id, $email);

				if ($subscriber !== null)
				{
					if ((int) $subscriber->status !== SubscriberRepository::STATUS_SUBSCRIBED)
					{
						continue;
					}
				}
				elseif (!$defaultSubscribed)
				{
					continue;
				}

				$subscriber ??= $this->subscribers->ensureRecipient((int) $user->id, $email);
				$recipients[$normalized] = [
					'subscriber_id' => (int) $subscriber->id,
					'user_id' => (int) $user->id,
					'email' => $email,
					'email_normalized' => $normalized,
					'recipient_name' => RecipientName::resolve((string) $user->name, $email),
					'source' => 'user-group',
				];
			}
		}

		ksort($recipients, SORT_STRING);

		return array_values($recipients);
	}

}
