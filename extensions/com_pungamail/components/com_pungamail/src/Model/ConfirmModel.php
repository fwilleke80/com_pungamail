<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\Model;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Double-opt-in confirmation display model. */
final class ConfirmModel extends BaseDatabaseModel
{
	/**
	 * Returns a valid pending subscriber for the URL token.
	 *
	 * @return object|null Pending subscriber.
	 */
	public function getSubscriber(): ?object
	{
		$token = Factory::getApplication()->getInput()->getString('token');

		if ($token === '')
		{
			return null;
		}

		$hash = ServiceFactory::tokens()->hashConfirmationToken($token);
		$now = (new Date('now', 'UTC'))->toSql();

		if (Factory::getApplication()->getInput()->getCmd('kind') === 'topics')
		{
			$request = ServiceFactory::topics()->findPreferenceRequest($hash);
			return $request !== null ? (object) ['id' => (int) $request->subscriber_id, 'email' => (string) $request->email] : null;
		}
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['id', 'email'])
			->from($db->quoteName('#__pungamail_subscribers'))
			->where($db->quoteName('confirmation_token_hash') . ' = :hash')
			->where($db->quoteName('confirmation_expires') . ' >= :now')
			->bind(':hash', $hash)
			->bind(':now', $now);

		return $db->setQuery($query)->loadObject() ?: null;
	}
}
