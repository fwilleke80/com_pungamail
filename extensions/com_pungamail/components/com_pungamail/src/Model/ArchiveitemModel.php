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
use Joomla\Database\ParameterType;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRepository;

/** Loads one publicly archived immutable newsletter snapshot. */
final class ArchiveitemModel extends BaseDatabaseModel
{
	/** @return object|null */
	public function getNewsletter(): ?object
	{
		$id = Factory::getApplication()->getInput()->getInt('id');
		if ($id <= 0)
		{
			return null;
		}

		$db = $this->getDatabase();
		$defaultVisible = (int) ComponentHelper::getParams('com_pungamail')->get('public_archive_default', 0) === 1;
		$query = $db->getQuery(true)
			->select(['n.id', 'n.title', 'n.snapshot_subject', 'n.snapshot_html', 'n.sent_at'])
			->from($db->quoteName('#__pungamail_newsletters', 'n'))
			->where('n.' . $db->quoteName('id') . ' = :id')
			->whereIn('n.' . $db->quoteName('status'), [NewsletterRepository::STATUS_SENT, NewsletterRepository::STATUS_SENT_WITH_FAILURES], ParameterType::INTEGER)
			->whereIn('n.' . $db->quoteName('state'), [1, 2], ParameterType::INTEGER)
			->where('n.' . $db->quoteName('sent_count') . ' > 0')
			->where('n.' . $db->quoteName('snapshot_html') . ' IS NOT NULL')
			->bind(':id', $id, ParameterType::INTEGER);

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

		$topicIds = $this->topicIds();
		if ($topicIds !== [])
		{
			$sub = $db->getQuery(true)
				->select('1')
				->from($db->quoteName('#__pungamail_newsletter_topics', 'ant'))
				->where('ant.' . $db->quoteName('newsletter_id') . ' = n.' . $db->quoteName('id'))
				->whereIn('ant.' . $db->quoteName('topic_id'), $topicIds, ParameterType::INTEGER);
			$query->where('EXISTS (' . $sub . ')');
		}

		$row = $db->setQuery($query)->loadObject();
		if ($row === null)
		{
			return null;
		}

		$topicQuery = $db->getQuery(true)
			->select('t.' . $db->quoteName('title'))
			->from($db->quoteName('#__pungamail_newsletter_topics', 'nt'))
			->innerJoin($db->quoteName('#__pungamail_topics', 't') . ' ON t.' . $db->quoteName('id') . ' = nt.' . $db->quoteName('topic_id'))
			->where('nt.' . $db->quoteName('newsletter_id') . ' = :topicNewsletterId')
			->order('t.' . $db->quoteName('title') . ' ASC')
			->bind(':topicNewsletterId', $id, ParameterType::INTEGER);
		$row->topics = array_map('strval', $db->setQuery($topicQuery)->loadColumn());
		return $row;
	}

	/** @return array<int,int> */
	private function topicIds(): array
	{
		$value = Factory::getApplication()->getParams()->get('archive_topic_ids', []);
		$values = is_array($value) ? $value : preg_split('/\s*,\s*/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
		return array_values(array_unique(array_filter(array_map('intval', (array) $values), static fn (int $id): bool => $id > 0)));
	}
}
