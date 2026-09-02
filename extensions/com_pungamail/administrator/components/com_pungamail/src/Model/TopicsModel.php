<?php
/** @package Punga.Mail @subpackage Administrator.Model */
namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/** Joomla-standard mailing-topic list model. */
final class TopicsModel extends ListModel
{
	/** @param array<string,mixed> $config Model configuration. */
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'title', 'a.title', 'alias', 'a.alias', 'state', 'a.state', 'ordering', 'a.ordering'];
		parent::__construct($config);
	}

	/** @return void */
	protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
	{
		parent::populateState($ordering, $direction);
	}

	/** @return \Joomla\Database\DatabaseQuery */
	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['a.*', 'COUNT(DISTINCT CASE WHEN st.status = 1 THEN st.subscriber_id END) AS subscriber_count'])
			->from($db->quoteName('#__pungamail_topics', 'a'))
			->leftJoin($db->quoteName('#__pungamail_subscriber_topics', 'st') . ' ON st.topic_id = a.id')
			->group('a.id');
		$search = trim((string) $this->getState('filter.search'));

		if ($search !== '')
		{
			$like = '%' . str_replace(' ', '%', $search) . '%';
			$query->where('(' . $db->quoteName('a.title') . ' LIKE :title OR ' . $db->quoteName('a.alias') . ' LIKE :alias)')
				->bind(':title', $like)
				->bind(':alias', $like);
		}

		$state = (string) $this->getState('filter.state');

		if ($state === '')
		{
			$trashed = -2;
			$query->where($db->quoteName('a.state') . ' <> :trashed')->bind(':trashed', $trashed, ParameterType::INTEGER);
		}
		else
		{
			$stateValue = (int) $state;
			$query->where($db->quoteName('a.state') . ' = :state')->bind(':state', $stateValue, ParameterType::INTEGER);
		}

		$order = (string) $this->getState('list.ordering', 'a.ordering');
		$direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
		$query->order($db->escape($order) . ' ' . $direction);

		return $query;
	}
}
