<?php
/**
 * @package     Punga.Mail
 * @subpackage  Plugin.User
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Plugin\User\PungaMail\Extension;

use Joomla\CMS\Event\Model\PrepareDataEvent;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\User\AfterSaveEvent;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Adds Punga Mail subscription state to Joomla user forms.
 */
final class PungaMail extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;

	/**
	 * Declares subscribed Joomla events.
	 *
	 * @return array<string,string> Event map.
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onContentPrepareData' => 'onContentPrepareData',
			'onContentPrepareForm' => 'onContentPrepareForm',
			'onUserAfterSave' => 'onUserAfterSave',
		];
	}

	/**
	 * Prefills the global preference and published topic memberships.
	 *
	 * @param PrepareDataEvent $event Joomla prepare-data event.
	 *
	 * @return void
	 */
	public function onContentPrepareData(PrepareDataEvent $event): void
	{
		$context = $event->getContext();

		if (!in_array($context, ['com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile'], true))
		{
			return;
		}

		$data = $event->getData();

		if (!is_object($data))
		{
			return;
		}

		$userId = (int) ($data->id ?? 0);
		$email = (string) ($data->email ?? '');
		$subscribed = (int) ComponentHelper::getParams('com_pungamail')->get('default_user_subscribed', 0) === 1;

		$topicIds = [];

		if ($userId > 0 && $email !== '')
		{
			$subscribers = ServiceFactory::subscribers();
			$subscribed = $subscribers->isUserSubscribed($userId, $email);
			$subscriber = $subscribers->findByUserId($userId) ?? $subscribers->findByEmail($email);

			if ($subscriber !== null)
			{
				$topicIds = ServiceFactory::topics()->getSubscriberTopicIds((int) $subscriber->id);
			}
		}

		$data->pungamail = [
			'subscribed' => $subscribed ? 1 : 0,
			'topic_ids' => $topicIds,
		];
		$event->updateData($data);
	}

	/**
	 * Injects the newsletter preference into Joomla user/profile forms.
	 *
	 * @param PrepareFormEvent $event Joomla prepare-form event.
	 *
	 * @return void
	 */
	public function onContentPrepareForm(PrepareFormEvent $event): void
	{
		$form = $event->getForm();

		if (!$form instanceof Form)
		{
			return;
		}

		if (!in_array($form->getName(), ['com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile'], true))
		{
			return;
		}

		Form::addFormPath(__DIR__ . '/../../forms');
		$form->loadFile('pungamail', false);
	}

	/**
	 * Persists the submitted global preference and published topic choices.
	 *
	 * @param AfterSaveEvent $event Joomla user-after-save event.
	 *
	 * @return void
	 */
	public function onUserAfterSave(AfterSaveEvent $event): void
	{
		if (!$event->getSavingResult())
		{
			return;
		}

		$user = $event->getUser();
		$formData = (array) Factory::getApplication()->getInput()->post->get('jform', [], 'array');
		$preference = $user['pungamail']['subscribed'] ?? $formData['pungamail']['subscribed'] ?? null;

		if ($preference === null)
		{
			return;
		}

		$userId = (int) ($user['id'] ?? 0);
		$email = (string) ($user['email'] ?? '');

		if ($userId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return;
		}

		$app = Factory::getApplication();
		$eventSource = $app->isClient('administrator') ? 'administrator' : 'profile';
		$subscribers = ServiceFactory::subscribers();
		$topics = ServiceFactory::topics();
		$subscriberId = $subscribers->setUserPreference(
			$userId,
			$email,
			(int) $preference === 1,
			(string) ($user['name'] ?? ''),
			$eventSource
		);
		$visibleIds = array_map(
			static fn (object $topic): int => (int) $topic->id,
			$topics->activeForUser($userId)
		);
		$selectedIds = array_values(array_unique(array_filter(array_map(
			'intval',
			(array) ($user['pungamail']['topic_ids'] ?? $formData['pungamail']['topic_ids'] ?? [])
		))));
		$previousIds = $topics->getSubscriberTopicIds($subscriberId);
		$topics->updateVisibleTopics($subscriberId, $visibleIds, $selectedIds);
		$currentIds = $topics->getSubscriberTopicIds($subscriberId);
		sort($previousIds, SORT_NUMERIC);
		sort($currentIds, SORT_NUMERIC);

		if ($previousIds !== $currentIds)
		{
			$subscribers->recordEvent(
				$subscriberId,
				$eventSource . '_topics_updated',
				null,
				null,
				['topic_ids' => $currentIds]
			);
		}
	}
}
