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
 * Joomla-standard subscriber administration list model.
 */
final class SubscribersModel extends ListModel
{
	/**
	 * @param array<string,mixed> $config Model configuration.
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 's.id',
				'email', 's.email',
				'source', 's.source',
				'status', 's.status',
				'confirmed_at', 's.confirmed_at',
				'suppression_reason', 'x.reason',
				'created', 's.created',
				'bounce_count', 's.bounce_count',
				'last_bounce_at', 's.last_bounce_at',
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
	protected function populateState($ordering = 's.created', $direction = 'DESC'): void
	{
		parent::populateState($ordering, $direction);
	}

	/**
	 * Builds the subscriber list query.
	 *
	 * @return \Joomla\Database\DatabaseQuery Query.
	 */
	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				's.*',
				'x.reason AS suppression_reason',
				'x.created AS suppressed_at',
				'u.name AS user_name',
			])
			->from($db->quoteName('#__pungamail_subscribers', 's'))
			->leftJoin($db->quoteName('#__pungamail_suppressions', 'x') . ' ON x.email_normalized = s.email_normalized')
			->leftJoin($db->quoteName('#__users', 'u') . ' ON u.id = s.user_id');

		$search = trim((string) $this->getState('filter.search'));

		if ($search !== '')
		{
			if (str_starts_with($search, 'id:'))
			{
				$id = (int) substr($search, 3);
				$query->where($db->quoteName('s.id') . ' = :searchId')
					->bind(':searchId', $id, ParameterType::INTEGER);
			}
			else
			{
				$like = '%' . str_replace(' ', '%', $search) . '%';
				$query->where('(' . $db->quoteName('s.email') . ' LIKE :searchEmail OR ' . $db->quoteName('u.name') . ' LIKE :searchName)')
					->bind(':searchEmail', $like)
					->bind(':searchName', $like);
			}
		}

		$status = (string) $this->getState('filter.status');

		if ($status !== '')
		{
			$statusValue = (int) $status;
			$query->where($db->quoteName('s.status') . ' = :status')
				->bind(':status', $statusValue, ParameterType::INTEGER);
		}

		$suppressed = (string) $this->getState('filter.suppressed');

		if ($suppressed === '1')
		{
			$query->where($db->quoteName('x.id') . ' IS NOT NULL');
		}
		elseif ($suppressed === '0')
		{
			$query->where($db->quoteName('x.id') . ' IS NULL');
		}

		$orderColumn = (string) $this->getState('list.ordering', 's.created');
		$orderDirection = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
		$query->order($db->escape($orderColumn) . ' ' . $orderDirection);

		return $query;
	}
}
