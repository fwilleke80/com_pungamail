<?php
/**
 * @package     Punga.Mail
 * @subpackage  Module.Signup
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Module\PungaMailSignup\Site\Helper;

use Joomla\CMS\Factory;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Signup module data helper. */
final class PungaMailSignupHelper
{
	/**
	 * Returns the current visitor's newsletter state.
	 *
	 * @return array{logged_in:bool,email:string,subscribed:bool}
	 */
	public static function getState(): array
	{
		$user = Factory::getApplication()->getIdentity();

		if ((int) $user->id <= 0)
		{
			return ['logged_in' => false, 'email' => '', 'subscribed' => false];
		}

		$email = (string) $user->email;

		return [
			'logged_in' => true,
			'email' => $email,
			'subscribed' => ServiceFactory::subscribers()->isUserSubscribed((int) $user->id, $email),
		];
	}
}
