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

/**
 * Joomla-standard newsletter list model.
 */
final class NewslettersModel extends ListModel
{
	/**
	 * @param array<string,mixed> $config Model configuration.
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'a.id',
				'title', 'a.title',
				'subject', 'a.subject',
				'state', 'a.state',
				'status', 'a.status',
				'recipient_count', 'a.recipient_count',
				'sent_count', 'a.sent_count',
				'failed_count', 'a.failed_count',
				'sent_at', 'a.sent_at',
				'scheduled_at', 'a.scheduled_at',
				'created', 'a.created',
			];
		}

		parent::__construct($config);
	}

	/**
	 * Populates persistent list/filter state.
	 *
	 * @param string $ordering  Default ordering column.
	 * @param string $direction Default ordering direction.
	 *
	 * @return void
	 */
	protected function populateState($ordering = 'a.created', $direction = 'DESC'): void
	{
		parent::populateState($ordering, $direction);
	}

	/**
	 * Builds the newsletter list query.
	 *
	 * @return \Joomla\Database\DatabaseQuery Query.
	 */
	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('a.*')
			->from($db->quoteName('#__pungamail_newsletters', 'a'));

		$search = trim((string) $this->getState('filter.search'));

		if ($search !== '')
		{
			if (str_starts_with($search, 'id:'))
			{
				$id = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :searchId')
					->bind(':searchId', $id, ParameterType::INTEGER);
			}
			else
			{
				$like = '%' . str_replace(' ', '%', $search) . '%';
				$query->where('(' . $db->quoteName('a.title') . ' LIKE :searchTitle OR ' . $db->quoteName('a.subject') . ' LIKE :searchSubject)')
					->bind(':searchTitle', $like)
					->bind(':searchSubject', $like);
			}
		}

		$state = (string) $this->getState('filter.state');

		if ($state === '')
		{
			$trashed = -2;
			$query->where($db->quoteName('a.state') . ' <> :trashed')
				->bind(':trashed', $trashed, ParameterType::INTEGER);
		}
		else
		{
			$stateValue = (int) $state;
			$query->where($db->quoteName('a.state') . ' = :state')
				->bind(':state', $stateValue, ParameterType::INTEGER);
		}

		$status = (string) $this->getState('filter.status');

		if ($status !== '')
		{
			$statusValue = (int) $status;
			$query->where($db->quoteName('a.status') . ' = :status')
				->bind(':status', $statusValue, ParameterType::INTEGER);
		}

		$orderColumn = (string) $this->getState('list.ordering', 'a.created');
		$orderDirection = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
		$query->order($db->escape($orderColumn) . ' ' . $orderDirection);

		return $query;
	}
}
