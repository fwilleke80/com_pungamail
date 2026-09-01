<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Subscriber administration list model.
 */
final class SubscribersModel extends BaseDatabaseModel
{
	/**
	 * Returns subscriber rows with suppression information.
	 *
	 * @return array<int,object> Subscriber rows.
	 */
	public function getItems(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				's.*',
				'x.reason AS suppression_reason',
				'u.name AS user_name',
			])
			->from($db->quoteName('#__pungamail_subscribers', 's'))
			->leftJoin($db->quoteName('#__pungamail_suppressions', 'x') . ' ON x.email_normalized = s.email_normalized')
			->leftJoin($db->quoteName('#__users', 'u') . ' ON u.id = s.user_id')
			->order('s.created DESC');

		return $db->setQuery($query)->loadObjectList();
	}
}
