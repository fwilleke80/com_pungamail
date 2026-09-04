<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Suppression-safe UTF-8 CSV import and export. */
final class CsvService
{
	/**
	 * @param DatabaseInterface     $db          Database connection.
	 * @param SubscriberRepository $subscribers Subscriber repository.
	 * @param TopicRepository      $topics      Topic repository.
	 */
	public function __construct(
		private readonly DatabaseInterface $db,
		private readonly SubscriberRepository $subscribers,
		private readonly TopicRepository $topics
	)
	{
	}

	/** @return array{headers:array<int,string>,rows:array<int,array<int,string>>,delimiter:string,total:int} */
	public function preview(string $contents): array
	{
		$parsed = $this->parse($contents);

		return [
			'headers' => $parsed['headers'],
			'rows' => array_slice($parsed['rows'], 0, 20),
			'delimiter' => $parsed['delimiter'],
			'total' => count($parsed['rows']),
		];
	}

	/** @return array<string,int> */
	public function import(string $contents, array $mapping, bool $reactivate): array
	{
		$parsed = $this->parse($contents);
		$headers = $parsed['headers'];
		$indices = [];

		foreach (['email', 'name', 'status', 'topics'] as $field)
		{
			$header = (string) ($mapping[$field] ?? '');
			$index = array_search($header, $headers, true);
			$indices[$field] = $index !== false ? (int) $index : null;
		}

		if ($indices['email'] === null)
		{
			throw new \InvalidArgumentException(\Joomla\CMS\Language\Text::_('COM_PUNGAMAIL_CSV_EMAIL_MAPPING_REQUIRED'));
		}

		$topicMap = [];

		foreach ($this->topics->active() as $topic)
		{
			$topicMap[mb_strtolower((string) $topic->alias, 'UTF-8')] = (int) $topic->id;
			$topicMap[mb_strtolower((string) $topic->title, 'UTF-8')] = (int) $topic->id;
		}

		$result = ['added' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'invalid' => 0, 'conflicts' => 0, 'errors' => 0];
		$seen = [];

		foreach ($parsed['rows'] as $row)
		{
			try
			{
				$email = trim((string) ($row[$indices['email']] ?? ''));

				if (!filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					$result['invalid']++;
					continue;
				}

				$normalized = Address::normalize($email);

				if (isset($seen[$normalized]))
				{
					$result['skipped']++;
					continue;
				}

				$seen[$normalized] = true;
				$name = $indices['name'] !== null ? trim((string) ($row[$indices['name']] ?? '')) : '';
				$statusValue = $indices['status'] !== null ? mb_strtolower(trim((string) ($row[$indices['status']] ?? '')), 'UTF-8') : '';
				$wantsUnsubscribed = in_array($statusValue, ['unsubscribed', 'inactive', '2', 'no', 'nein'], true);
				$wantsActive = in_array($statusValue, ['active', 'subscribed', '1', 'yes', 'ja'], true);
				$subscriber = $this->subscribers->findByEmail($email);
				$isSuppressed = $this->subscribers->isSuppressed($normalized);
				$isProtected = $isSuppressed || ($subscriber !== null && (
					(int) $subscriber->status === SubscriberRepository::STATUS_UNSUBSCRIBED
					|| (string) ($subscriber->last_bounce_class ?? '') === 'hard'
				));
				$isNew = false;
				$changed = false;

				if ($subscriber === null)
				{
					if ($isProtected && !($wantsActive && $reactivate))
					{
						$result['conflicts']++;
						continue;
					}

					$subscriberId = $this->subscribers->addAdministratorExternal($email);
					$subscriber = $this->subscribers->findById($subscriberId);
					$isNew = true;
				}
				elseif ($isProtected && $wantsActive && !$reactivate)
				{
					$result['conflicts']++;
					continue;
				}
				elseif ($isProtected && $wantsActive && $reactivate)
				{
					$subscriberId = $this->subscribers->addAdministratorExternal($email);
					$subscriber = $this->subscribers->findById($subscriberId);
					$changed = true;
				}
				else
				{
					$subscriberId = (int) $subscriber->id;
				}

				if ($name !== '' && $name !== (string) ($subscriber->recipient_name ?? ''))
				{
					$this->subscribers->updateRecipientName($subscriberId, $name);
					$changed = true;
				}

				if ($wantsUnsubscribed && (
					(int) ($subscriber->status ?? SubscriberRepository::STATUS_SUBSCRIBED) !== SubscriberRepository::STATUS_UNSUBSCRIBED
					|| !$isSuppressed
				))
				{
					$this->subscribers->unsubscribe($subscriberId, 'csv-import');
					$changed = true;
				}

				if ($indices['topics'] !== null)
				{
					$topicNames = preg_split('/[|;,]+/u', (string) ($row[$indices['topics']] ?? '')) ?: [];
					$topicIds = [];

					foreach ($topicNames as $topicName)
					{
						$key = mb_strtolower(trim($topicName), 'UTF-8');

						if (isset($topicMap[$key]))
						{
							$topicIds[] = $topicMap[$key];
						}
					}

					$currentTopicIds = $this->topics->getSubscriberTopicIds($subscriberId);
					$newTopicIds = array_values(array_diff(array_unique($topicIds), $currentTopicIds));

					if ($newTopicIds !== [])
					{
						$this->topics->subscribeTopics($subscriberId, $newTopicIds);
						$changed = true;
					}
				}

				if ($isNew)
				{
					$result['added']++;
				}
				elseif ($changed)
				{
					$result['updated']++;
				}
				else
				{
					$result['unchanged']++;
				}
			}
			catch (\Throwable)
			{
				$result['errors']++;
			}
		}

		return $result;
	}

	/** @return array<int,array<string,string|int>> */
	public function export(string $scope, array $topicIds = []): array
	{
		$activeMembership = TopicRepository::MEMBERSHIP_SUBSCRIBED;
		$query = $this->db->getQuery(true)
			->select([
				's.id', 's.email', 's.recipient_name', 's.status', 's.source', 's.language',
				's.bounce_count', 's.soft_bounce_count', 's.last_bounce_at', 's.last_bounce_class', 's.last_bounce_reason',
				'x.reason AS suppression_reason',
				"GROUP_CONCAT(DISTINCT CASE WHEN st.status = " . $activeMembership . " THEN t.alias END ORDER BY t.ordering SEPARATOR '|') AS topics",
			])
			->from($this->db->quoteName('#__pungamail_subscribers', 's'))
			->leftJoin($this->db->quoteName('#__pungamail_suppressions', 'x') . ' ON x.email_normalized = s.email_normalized')
			->leftJoin($this->db->quoteName('#__pungamail_subscriber_topics', 'st') . ' ON st.subscriber_id = s.id')
			->leftJoin($this->db->quoteName('#__pungamail_topics', 't') . ' ON t.id = st.topic_id')
			->group('s.id');

		if ($scope === 'active')
		{
			$status = SubscriberRepository::STATUS_SUBSCRIBED;
			$query->where($this->db->quoteName('s.status') . ' = :status')->where($this->db->quoteName('x.id') . ' IS NULL')->bind(':status', $status, ParameterType::INTEGER);
		}
		elseif ($scope === 'unsubscribed')
		{
			$status = SubscriberRepository::STATUS_UNSUBSCRIBED;
			$query->where($this->db->quoteName('s.status') . ' = :status')->bind(':status', $status, ParameterType::INTEGER);
		}
		elseif ($scope === 'suppressed')
		{
			$query->where($this->db->quoteName('x.id') . ' IS NOT NULL');
		}

		$topicIds = array_values(array_unique(array_filter(array_map('intval', $topicIds))));

		if ($topicIds !== [])
		{
			// Do not use Query::whereIn() on a subquery that is then stringified into
			// the parent query: its generated positional bindings are not transferred
			// to the parent statement. The IDs are normalized integers, so emitting
			// the literal list is safe and keeps the prepared-statement bindings exact.
			$topicList = implode(',', $topicIds);
			$subQuery = $this->db->getQuery(true)
				->select('1')
				->from($this->db->quoteName('#__pungamail_subscriber_topics', 'filter_st'))
				->where($this->db->quoteName('filter_st.subscriber_id') . ' = s.id')
				->where($this->db->quoteName('filter_st.status') . ' = :membershipStatus')
				->where($this->db->quoteName('filter_st.topic_id') . ' IN (' . $topicList . ')');
			$query->where('EXISTS (' . $subQuery . ')')->bind(':membershipStatus', $activeMembership, ParameterType::INTEGER);
		}

		$query->order($this->db->quoteName('s.email_normalized') . ' ASC');
		$rows = [];

		foreach ($this->db->setQuery($query)->loadObjectList() as $row)
		{
			$rows[] = [
				'email' => (string) $row->email,
				'name' => (string) $row->recipient_name,
				'status' => (int) $row->status,
				'source' => (string) $row->source,
				'language' => (string) ($row->language ?? ''),
				'topics' => (string) ($row->topics ?? ''),
				'suppression_reason' => (string) ($row->suppression_reason ?? ''),
				'bounce_count' => (int) $row->bounce_count,
				'soft_bounce_count' => (int) $row->soft_bounce_count,
				'last_bounce_at' => (string) ($row->last_bounce_at ?? ''),
				'last_bounce_class' => (string) ($row->last_bounce_class ?? ''),
				'last_bounce_reason' => (string) ($row->last_bounce_reason ?? ''),
			];
		}

		return $rows;
	}

	/** @return array{headers:array<int,string>,rows:array<int,array<int,string>>,delimiter:string} */
	private function parse(string $contents): array
	{
		$contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

		if (strlen($contents) > 5 * 1024 * 1024)
		{
			throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_PUNGAMAIL_CSV_FILE_TOO_LARGE'));
		}

		$firstLine = strtok($contents, "\r\n") ?: '';
		$counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
		arsort($counts);
		$delimiter = (string) array_key_first($counts);
		$handle = fopen('php://temp', 'r+');

		if ($handle === false)
		{
			throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_PUNGAMAIL_CSV_PARSE_FAILED'));
		}

		fwrite($handle, $contents);
		rewind($handle);
		$headers = fgetcsv($handle, 0, $delimiter);
		$headers = is_array($headers) ? array_map(static fn (string $value): string => trim($value), $headers) : [];
		$rows = [];

		while (($row = fgetcsv($handle, 0, $delimiter)) !== false)
		{
			if (count($rows) >= 20000)
			{
				break;
			}

			if ($row === [null] || array_filter($row, static fn ($value): bool => trim((string) $value) !== '') === [])
			{
				continue;
			}

			$rows[] = array_map(static fn ($value): string => (string) $value, $row);
		}

		fclose($handle);

		if ($headers === [])
		{
			throw new \InvalidArgumentException(\Joomla\CMS\Language\Text::_('COM_PUNGAMAIL_CSV_HEADER_REQUIRED'));
		}

		return ['headers' => $headers, 'rows' => $rows, 'delimiter' => $delimiter];
	}
}
