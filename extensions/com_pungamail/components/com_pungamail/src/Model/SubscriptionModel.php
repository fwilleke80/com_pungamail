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
	 * @return array{logged_in:bool,email:string,subscribed:bool,topics:array<int,object>,selected_topic_ids:array<int,int>}
	 */
	public function getSubscriptionState(): array
	{
		$user = Factory::getApplication()->getIdentity();
		$topics = ServiceFactory::topics()->active();

		if ((int) $user->id <= 0)
		{
			return [
				'logged_in' => false,
				'email' => '',
				'subscribed' => false,
				'topics' => $topics,
				'selected_topic_ids' => [],
			];
		}

		$email = (string) $user->email;
		$subscribed = ServiceFactory::subscribers()->isUserSubscribed((int) $user->id, $email);
		$subscriber = ServiceFactory::subscribers()->findByUserId((int) $user->id)
			?? ServiceFactory::subscribers()->findByEmail($email);

		return [
			'logged_in' => true,
			'email' => $email,
			'subscribed' => $subscribed,
			'topics' => $topics,
			'selected_topic_ids' => $subscriber !== null
				? ServiceFactory::topics()->getSubscriberTopicIds((int) $subscriber->id)
				: [],
		];
	}
}
