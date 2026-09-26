<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Database\ParameterType;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRepository;

/** Public list of immutable sent newsletters selected for the archive. */
final class ArchiveModel extends BaseDatabaseModel
{
	/** @return array{items:array<int,object>,pagination:Pagination} */
	public function getArchive(): array
	{
		$app = Factory::getApplication();
		$params = $app->getParams();
		$limit = max(1, min(100, (int) $params->get('archive_page_size', 12)));
		$start = max(0, $app->getInput()->getInt('limitstart', 0));
		$topicIds = $this->topicIds();
		$db = $this->getDatabase();
		$count = $db->setQuery($this->query('COUNT(DISTINCT n.id)', $topicIds))->loadResult();
		$total = max(0, (int) $count);
		$pagination = new Pagination($total, $start, $limit);
		$items = $db->setQuery($this->query('DISTINCT n.id, n.title, n.snapshot_subject, n.sent_at', $topicIds), $pagination->limitstart, $pagination->limit)->loadObjectList();
		$this->attachTopics($items);

		return ['items' => $items, 'pagination' => $pagination];
	}

	/** @return array<int,int> */
	private function topicIds(): array
	{
		$value = Factory::getApplication()->getParams()->get('archive_topic_ids', []);
		$values = is_array($value) ? $value : preg_split('/\s*,\s*/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
		return array_values(array_unique(array_filter(array_map('intval', (array) $values), static fn (int $id): bool => $id > 0)));
	}

	/** @param array<int,int> $topicIds */
	private function query(string $select, array $topicIds): object
	{
		$db = $this->getDatabase();
		$defaultVisible = (int) ComponentHelper::getParams('com_pungamail')->get('public_archive_default', 0) === 1;
		$query = $db->getQuery(true)
			->select($select)
			->from($db->quoteName('#__pungamail_newsletters', 'n'))
			->whereIn('n.' . $db->quoteName('status'), [NewsletterRepository::STATUS_SENT, NewsletterRepository::STATUS_SENT_WITH_FAILURES], ParameterType::INTEGER)
			->whereIn('n.' . $db->quoteName('state'), [1, 2], ParameterType::INTEGER)
			->where('n.' . $db->quoteName('sent_count') . ' > 0')
			->where('n.' . $db->quoteName('snapshot_html') . ' IS NOT NULL');

		if ($defaultVisible)
		{
			$query->whereIn('n.' . $db->quoteName('archive_visibility'), [-1, 1], ParameterType::INTEGER);
		}
		else
		{
			$visible = 1;
			$query->where('n.' . $db->quoteName('archive_visibility') . ' = :archiveVisible')
				->bind(':archiveVisible', $visible, ParameterType::INTEGER);
		}

		if ($topicIds !== [])
		{
			$sub = $db->getQuery(true)
				->select('1')
				->from($db->quoteName('#__pungamail_newsletter_topics', 'ant'))
				->where('ant.' . $db->quoteName('newsletter_id') . ' = n.' . $db->quoteName('id'))
				->whereIn('ant.' . $db->quoteName('topic_id'), $topicIds, ParameterType::INTEGER);
			$query->where('EXISTS (' . $sub . ')');
		}

		if (!str_starts_with($select, 'COUNT('))
		{
			$query->order('n.' . $db->quoteName('sent_at') . ' DESC, n.' . $db->quoteName('id') . ' DESC');
		}

		return $query;
	}

	/** @param array<int,object> $items @return void */
	private function attachTopics(array $items): void
	{
		if ($items === [])
		{
			return;
		}

		$db = $this->getDatabase();
		$ids = array_map(static fn (object $item): int => (int) $item->id, $items);
		$query = $db->getQuery(true)
			->select(['nt.' . $db->quoteName('newsletter_id'), 't.' . $db->quoteName('title')])
			->from($db->quoteName('#__pungamail_newsletter_topics', 'nt'))
			->innerJoin($db->quoteName('#__pungamail_topics', 't') . ' ON t.' . $db->quoteName('id') . ' = nt.' . $db->quoteName('topic_id'))
			->whereIn('nt.' . $db->quoteName('newsletter_id'), $ids, ParameterType::INTEGER)
			->order('t.' . $db->quoteName('title') . ' ASC');
		$map = [];
		foreach ($db->setQuery($query)->loadObjectList() as $row)
		{
			$map[(int) $row->newsletter_id][] = (string) $row->title;
		}
		foreach ($items as $item)
		{
			$item->topics = $map[(int) $item->id] ?? [];
		}
	}
}
