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
	public static function getState(array $configuredTopicIds = []): array
	{
		$user = Factory::getApplication()->getIdentity();
		$topics = ServiceFactory::topics()->active($configuredTopicIds !== [] ? $configuredTopicIds : null);

		if ((int) $user->id <= 0)
		{
			return ['logged_in' => false, 'email' => '', 'subscribed' => false, 'topics' => $topics, 'selected_topic_ids' => []];
		}

		$email = (string) $user->email;
		$subscriber = ServiceFactory::subscribers()->findByUserId((int) $user->id) ?? ServiceFactory::subscribers()->findByEmail($email);

		return [
			'logged_in' => true,
			'email' => $email,
			'subscribed' => ServiceFactory::subscribers()->isUserSubscribed((int) $user->id, $email),
			'topics' => $topics,
			'selected_topic_ids' => $subscriber !== null ? ServiceFactory::topics()->getSubscriberTopicIds((int) $subscriber->id) : [],
		];
	}
}
