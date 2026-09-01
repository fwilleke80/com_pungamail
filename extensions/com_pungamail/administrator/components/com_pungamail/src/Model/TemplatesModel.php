<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/** Joomla-standard template list model. */
final class TemplatesModel extends ListModel
{
	/** @param array<string,mixed> $config Model configuration. */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'subject', 'a.subject', 'state', 'a.state', 'modified', 'a.modified'];
		}
		parent::__construct($config);
	}

	/** @return void */
	protected function populateState($ordering = 'a.modified', $direction = 'DESC'): void
	{
		parent::populateState($ordering, $direction);
	}

	/** @return \Joomla\Database\DatabaseQuery */
	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)->select('a.*')->from($db->quoteName('#__pungamail_templates', 'a'));
		$search = trim((string) $this->getState('filter.search'));

		if ($search !== '')
		{
			if (str_starts_with($search, 'id:'))
			{
				$id = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :searchId')->bind(':searchId', $id, ParameterType::INTEGER);
			}
			else
			{
				$like = '%' . str_replace(' ', '%', $search) . '%';
				$query->where('(' . $db->quoteName('a.title') . ' LIKE :searchTitle OR ' . $db->quoteName('a.subject') . ' LIKE :searchSubject)')
					->bind(':searchTitle', $like)->bind(':searchSubject', $like);
			}
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

		$orderColumn = (string) $this->getState('list.ordering', 'a.modified');
		$orderDirection = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
		$query->order($db->escape($orderColumn) . ' ' . $orderDirection);
		return $query;
	}
}
