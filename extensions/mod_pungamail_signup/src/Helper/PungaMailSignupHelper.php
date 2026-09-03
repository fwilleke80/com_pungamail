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
	 * @return array{logged_in:bool,email:string,subscribed:bool,topics:array<int,object>,selected_topic_ids:array<int,int>,single_topic_mode:bool}
	 */
	public static function getState(array $configuredTopicIds = []): array
	{
		$configuredTopicIds = array_values(array_unique(array_filter(array_map('intval', $configuredTopicIds))));
		$user = Factory::getApplication()->getIdentity();
		$topics = ServiceFactory::topics()->active($configuredTopicIds !== [] ? $configuredTopicIds : null);
		$singleTopicMode = count($configuredTopicIds) === 1;

		if ((int) $user->id <= 0)
		{
			return [
				'logged_in' => false,
				'email' => '',
				'subscribed' => false,
				'topics' => $topics,
				'selected_topic_ids' => [],
				'single_topic_mode' => $singleTopicMode,
			];
		}

		$email = (string) $user->email;
		$subscriber = ServiceFactory::subscribers()->findByUserId((int) $user->id) ?? ServiceFactory::subscribers()->findByEmail($email);

		return [
			'logged_in' => true,
			'email' => $email,
			'subscribed' => ServiceFactory::subscribers()->isUserSubscribed((int) $user->id, $email),
			'topics' => $topics,
			'selected_topic_ids' => $subscriber !== null ? ServiceFactory::topics()->getSubscriberTopicIds((int) $subscriber->id) : [],
			'single_topic_mode' => $singleTopicMode,
		];
	}
}
