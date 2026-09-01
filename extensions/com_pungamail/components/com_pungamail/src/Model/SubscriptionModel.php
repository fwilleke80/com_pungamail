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

/**
 * Provides the public newsletter-subscription page state.
 */
final class SubscriptionModel extends BaseDatabaseModel
{
	/**
	 * Returns the current visitor's newsletter subscription state.
	 *
	 * @return array{logged_in:bool,email:string,subscribed:bool}
	 */
	public function getState(): array
	{
		$user = Factory::getApplication()->getIdentity();

		if ((int) $user->id <= 0)
		{
			return [
				'logged_in' => false,
				'email' => '',
				'subscribed' => false,
			];
		}

		$email = (string) $user->email;

		return [
			'logged_in' => true,
			'email' => $email,
			'subscribed' => ServiceFactory::subscribers()->isUserSubscribed((int) $user->id, $email),
		];
	}
}
