<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Mail delivery, bounce and queue diagnostics model. */
final class DeliveryModel extends BaseDatabaseModel
{
	/** @return object */
	public function getSettings(): object
	{
		return ServiceFactory::mailSettings()->getPublic();
	}

	/** @return array<int,object> */
	public function getBounces(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['b.*', 's.id AS current_subscriber_id', 'x.reason AS suppression_reason'])
			->from($db->quoteName('#__pungamail_bounces', 'b'))
			->leftJoin($db->quoteName('#__pungamail_subscribers', 's') . ' ON s.id = b.subscriber_id')
			->leftJoin($db->quoteName('#__pungamail_suppressions', 'x') . ' ON x.email_normalized = b.email_normalized')
			->order($db->quoteName('b.occurred_at') . ' DESC');

		return $db->setQuery($query, 0, 100)->loadObjectList();
	}

	/** @return array<string,mixed> */
	public function getDiagnostics(): array
	{
		$config = Factory::getConfig();
		$params = ComponentHelper::getParams('com_pungamail');
		$sender = ServiceFactory::mailConfiguration()->sender();

		return [
			'mailer' => (string) $config->get('mailer', ''),
			'sender_email' => $sender['email'],
			'sender_valid' => filter_var($sender['email'], FILTER_VALIDATE_EMAIL) !== false,
			'queue_paused' => (int) $params->get('queue_paused', 0) === 1,
			'batch_size' => (int) $params->get('batch_size', 25),
			'max_attempts' => (int) $params->get('max_attempts', 3),
			'retry_minutes' => (int) $params->get('retry_minutes', 15),
			'imap_available' => function_exists('imap_open'),
		];
	}
}
