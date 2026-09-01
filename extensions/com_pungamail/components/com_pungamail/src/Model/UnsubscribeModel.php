<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Visible unsubscribe confirmation model. */
final class UnsubscribeModel extends BaseDatabaseModel
{
	/**
	 * Returns the subscriber only when the signed URL is valid.
	 *
	 * @return object|null Subscriber row.
	 */
	public function getSubscriber(): ?object
	{
		$input = Factory::getApplication()->getInput();
		$id = $input->getInt('id');
		$token = $input->getString('token');
		$subscriber = ServiceFactory::subscribers()->findById($id);

		if ($subscriber === null)
		{
			return null;
		}

		return ServiceFactory::tokens()->validateUnsubscribeToken($id, $token)
			? $subscriber
			: null;
	}
}
