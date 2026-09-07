<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Controller
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;
use Punga\Component\PungaMail\Administrator\Service\SubscriberRepository;

/**
 * Subscriber administration actions.
 */
final class SubscriberController extends BaseController
{

	/** @return void */
	public function add(): void
	{
		$this->guardCreate(false);
		$this->setRedirect(Route::_(AdministratorRoute::subscriber(), false));
	}

	/** @return void */
	public function save(): void
	{
		$this->saveSubscriber(false);
	}

	/** @return void */
	public function save2close(): void
	{
		$this->saveSubscriber(true);
	}

	/**
	 * Creates or updates a subscriber without coupling topic changes to consent.
	 *
	 * @param bool $close Return to the list after saving.
	 *
	 * @return void
	 */
	private function saveSubscriber(bool $close): void
	{
		$app = Factory::getApplication();
		$data = (array) $app->getInput()->post->get('jform', [], 'array');
		$id = max(0, (int) ($data['id'] ?? 0));
		$isUpdate = $id > 0;
		$this->guardSave($id);
		$type = (string) ($data['recipient_type'] ?? 'email');
		$recipientName = trim((string) ($data['recipient_name'] ?? ''));

		try
		{
			if ($id > 0)
			{
				$id = $this->updateExisting($id, $data, $recipientName);
			}
			elseif ($type === 'user')
			{
				$userId = (int) ($data['user_id'] ?? 0);
				if ($userId <= 0)
				{
					throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_USER_REQUIRED'));
				}

				$db = ServiceFactory::database();
				$query = $db->getQuery(true)
					->select([$db->quoteName('id'), $db->quoteName('name'), $db->quoteName('email'), $db->quoteName('block')])
					->from($db->quoteName('#__users'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $userId, \Joomla\Database\ParameterType::INTEGER);
				$user = $db->setQuery($query)->loadObject();

				if ($user === null || (int) $user->block === 1 || !filter_var((string) $user->email, FILTER_VALIDATE_EMAIL))
				{
					throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_USER_NOT_ELIGIBLE'));
				}

				$this->assertNewSubscriberAvailable(
					ServiceFactory::subscribers()->findByUserId((int) $user->id)
						?? ServiceFactory::subscribers()->findByEmail((string) $user->email)
				);

				$id = ServiceFactory::subscribers()->setUserPreference(
					(int) $user->id,
					(string) $user->email,
					true,
					(string) $user->name,
					'administrator'
				);
			}
			else
			{
				$email = trim((string) ($data['email'] ?? ''));

				if (!filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_VALID_EMAIL_REQUIRED'));
				}

				$this->assertNewSubscriberAvailable(ServiceFactory::subscribers()->findByEmail($email));

				$id = ServiceFactory::subscribers()->addAdministratorExternal($email);
				ServiceFactory::subscribers()->updateRecipientName($id, $recipientName);
			}

			$this->saveTopics($id, (array) ($data['topic_ids'] ?? []));
			$this->setRedirect(
				Route::_($close ? AdministratorRoute::subscribers() : AdministratorRoute::subscriber($id), false),
				Text::_($isUpdate ? 'COM_PUNGAMAIL_SUBSCRIBER_SAVED' : 'COM_PUNGAMAIL_SUBSCRIBER_ADDED')
			);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::subscriber($id), false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function cancel(): void
	{
		$data = (array) Factory::getApplication()->getInput()->post->get('jform', [], 'array');
		$id = max(0, (int) ($data['id'] ?? Factory::getApplication()->getInput()->getInt('id')));
		$this->guardSave($id, false);
		$this->setRedirect(Route::_(AdministratorRoute::subscribers(), false));
	}

	/**
	 * Updates the editable identity and raw subscription state.
	 *
	 * @param int                 $id            Subscriber ID.
	 * @param array<string,mixed> $data          Submitted form data.
	 * @param string              $recipientName External recipient display name.
	 *
	 * @return int Subscriber ID.
	 */
	private function updateExisting(int $id, array $data, string $recipientName): int
	{
		$repo = ServiceFactory::subscribers();
		$subscriber = $repo->findById($id);

		if ($subscriber === null)
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_SUBSCRIBER_NOT_FOUND'));
		}

		$currentStatus = (int) $subscriber->status;
		$newStatus = (int) ($data['status'] ?? $currentStatus);

		if (!in_array($newStatus, [
			SubscriberRepository::STATUS_PENDING,
			SubscriberRepository::STATUS_SUBSCRIBED,
			SubscriberRepository::STATUS_UNSUBSCRIBED,
		], true))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_SUBSCRIPTION_STATUS'));
		}

		if ($newStatus === SubscriberRepository::STATUS_PENDING && $currentStatus !== SubscriberRepository::STATUS_PENDING)
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_PENDING_STATUS'));
		}

		if ($newStatus !== $currentStatus)
		{
			if ($newStatus === SubscriberRepository::STATUS_UNSUBSCRIBED)
			{
				$repo->unsubscribe($id, 'administrator');
			}
			elseif ($subscriber->user_id !== null)
			{
				$user = Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById((int) $subscriber->user_id);

				if ((int) $user->id <= 0 || (int) $user->block === 1 || !filter_var((string) $user->email, FILTER_VALIDATE_EMAIL))
				{
					throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_USER_NOT_ELIGIBLE'));
				}

				$id = $repo->setUserPreference((int) $user->id, (string) $user->email, true, (string) $user->name, 'administrator');
			}
			else
			{
				$id = $repo->addAdministratorExternal((string) $subscriber->email);
			}
		}

		if ($subscriber->user_id === null)
		{
			$repo->updateRecipientName($id, $recipientName);
		}

		return $id;
	}

	/**
	 * Replaces memberships for all non-trashed Channels available to administrators.
	 *
	 * @param int                 $subscriberId Subscriber ID.
	 * @param array<int,mixed>    $requestedIds Submitted topic IDs.
	 *
	 * @return void
	 */
	private function saveTopics(int $subscriberId, array $requestedIds): void
	{
		$topics = ServiceFactory::topics();
		$subscriber = ServiceFactory::subscribers()->findById($subscriberId);
		$userId = $subscriber !== null && $subscriber->user_id !== null ? (int) $subscriber->user_id : null;
		$visibleIds = array_map(static fn (object $topic): int => (int) $topic->id, $topics->availableForAdministration());
		$eligibleIds = $topics->eligibleIds($visibleIds, $userId, false);
		$selectedIds = array_values(array_intersect($eligibleIds, array_map('intval', $requestedIds)));
		$previousIds = $topics->getSubscriberTopicIds($subscriberId);
		$topics->updateAdministratorTopics($subscriberId, $visibleIds, $selectedIds, $userId);

		$currentVisibleIds = array_values(array_intersect($eligibleIds, $previousIds));
		sort($currentVisibleIds);
		sort($selectedIds);

		if ($currentVisibleIds !== $selectedIds)
		{
			ServiceFactory::subscribers()->recordEvent($subscriberId, 'administrator_topics_updated', null, null, ['topic_ids' => $selectedIds]);
		}
	}

	/**
	 * Unsubscribes a subscriber from the administrator UI.
	 *
	 * @return void
	 */
	public function unsubscribe(): void
	{
		$this->guard();
		$id = Factory::getApplication()->getInput()->getInt('id');
		ServiceFactory::subscribers()->unsubscribe($id, 'administrator');
		$this->setRedirect(Route::_(AdministratorRoute::subscribers(), false), Text::_('COM_PUNGAMAIL_SUBSCRIBER_SUPPRESSED'));
	}

	/**
	 * Sends a new double-opt-in confirmation request instead of force-subscribing.
	 *
	 * @return void
	 */
	public function requestConfirmation(): void
	{
		$this->guard();
		$id = Factory::getApplication()->getInput()->getInt('id');
		$repo = ServiceFactory::subscribers();
		$subscriber = $repo->findById($id);

		if ($subscriber === null)
		{
			$this->setRedirect(Route::_(AdministratorRoute::subscribers(), false), Text::_('COM_PUNGAMAIL_ERROR_SUBSCRIBER_NOT_FOUND'), 'error');
			return;
		}

		$tokenData = ServiceFactory::tokens()->createConfirmationToken();
		$hours = max(1, (int) ComponentHelper::getParams('com_pungamail')->get('confirmation_hours', 48));
		$expires = (new Date('+' . $hours . ' hours', 'UTC'))->toSql();
		$newId = $repo->storePendingExternal((string) $subscriber->email, $tokenData['hash'], $expires, (string) ($subscriber->language ?? 'en-GB'));
		ServiceFactory::mail()->sendConfirmation((string) $subscriber->email, $tokenData['token']);
		$repo->recordEvent($newId, 'confirmation_sent', null, null, ['source' => 'administrator']);
		$this->setRedirect(Route::_(AdministratorRoute::subscribers(), false), Text::_('COM_PUNGAMAIL_CONFIRMATION_SENT'));
	}

	/**
	 * Returns Channel eligibility for the current unsaved recipient selection.
	 *
	 * This keeps the editor responsive without duplicating Joomla group logic in
	 * JavaScript. Final save still performs the same server-side eligibility check.
	 *
	 * @return void
	 */
	public function channelEligibility(): void
	{
		$app = Factory::getApplication();

		try
		{
			$user = $app->getIdentity();

			if (!$user->authorise('core.create', 'com_pungamail') && !$user->authorise('core.edit', 'com_pungamail'))
			{
				throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			if (!Session::checkToken('post'))
			{
				throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
			}

			$recipientType = $app->getInput()->post->getCmd('recipient_type', 'email');
			$userId = max(0, $app->getInput()->post->getInt('user_id'));
			$email = trim((string) $app->getInput()->post->getString('email', ''));
			$effectiveUserId = null;
			$duplicate = null;
			$subscribers = ServiceFactory::subscribers();

			if ($recipientType === 'user' && $userId > 0)
			{
				$db = ServiceFactory::database();
				$query = $db->getQuery(true)
					->select([$db->quoteName('id'), $db->quoteName('email'), $db->quoteName('block')])
					->from($db->quoteName('#__users'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $userId, \Joomla\Database\ParameterType::INTEGER);
				$selectedUser = $db->setQuery($query)->loadObject();

				if ($selectedUser !== null && (int) $selectedUser->block === 0 && filter_var((string) $selectedUser->email, FILTER_VALIDATE_EMAIL))
				{
					$effectiveUserId = (int) $selectedUser->id;
					$existing = $subscribers->findByUserId($effectiveUserId) ?? $subscribers->findByEmail((string) $selectedUser->email);

					if ($existing !== null)
					{
						$duplicate = $this->duplicateResponse($existing);
					}
				}
			}
			elseif ($recipientType === 'email' && filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$existing = $subscribers->findByEmail($email);

				if ($existing !== null)
				{
					$duplicate = $this->duplicateResponse($existing);
				}
			}

			$topics = ServiceFactory::topics()->availableForAdministration();
			$ids = array_map(static fn (object $topic): int => (int) $topic->id, $topics);
			$eligibleIds = ServiceFactory::topics()->eligibleIds($ids, $effectiveUserId, false);
			echo new JsonResponse(['eligible_ids' => $eligibleIds, 'duplicate' => $duplicate]);
		}
		catch (\Throwable $e)
		{
			echo new JsonResponse(null, ErrorMessage::sanitize($e), true);
		}

		$app->close();
	}

	/** @return void */
	public function clearBounceSuppression(): void
	{
		$app = Factory::getApplication();
		$id = max(0, $app->getInput()->post->getInt('subscriber_id'));
		$returnContext = $app->getInput()->post->getCmd('return_context', 'subscribers');
		$this->guardBounceRecovery();
		$returnUrl = $returnContext === 'subscriber' && $id > 0
			? AdministratorRoute::subscriber($id)
			: AdministratorRoute::subscribers();

		if ($id <= 0 || !ServiceFactory::subscribers()->clearBounceSuppression($id))
		{
			$this->setRedirect(Route::_($returnUrl, false), Text::_('COM_PUNGAMAIL_BOUNCE_SUPPRESSION_NOT_CLEARED'), 'error');
			return;
		}

		$this->setRedirect(Route::_($returnUrl, false), Text::_('COM_PUNGAMAIL_BOUNCE_SUPPRESSION_CLEARED'));
	}


	/**
	 * Verifies permission to restore delivery after a bounce suppression.
	 *
	 * @return void
	 */
	private function guardBounceRecovery(): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}

	/**
	 * Verifies recipient-creation permission and optionally a CSRF token.
	 *
	 * @param bool $checkToken Whether a POST token is required.
	 *
	 * @return void
	 */
	private function guardCreate(bool $checkToken): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.create', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if ($checkToken && !Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}

	/**
	 * Verifies create/edit permission and optionally a CSRF token.
	 *
	 * @param int  $id         Subscriber ID, or zero for creation.
	 * @param bool $checkToken Whether a POST token is required.
	 *
	 * @return void
	 */
	private function guardSave(int $id, bool $checkToken = true): void
	{
		$permission = $id > 0 ? 'core.edit' : 'core.create';

		if (!Factory::getApplication()->getIdentity()->authorise($permission, 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if ($checkToken && !Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}

	/**
	 * Keeps the New Subscriber workflow create-only.
	 *
	 * @param object|null $subscriber Existing subscriber, when found.
	 *
	 * @return void
	 */
	private function assertNewSubscriberAvailable(?object $subscriber): void
	{
		if ($subscriber !== null)
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_SUBSCRIBER_ALREADY_EXISTS'));
		}
	}

	/**
	 * Builds the duplicate identity payload used by the live New Subscriber check.
	 *
	 * @param object $subscriber Existing subscriber row.
	 *
	 * @return array{id:int,url:string}
	 */
	private function duplicateResponse(object $subscriber): array
	{
		$id = (int) ($subscriber->id ?? 0);

		return [
			'id' => $id,
			'url' => Route::_(AdministratorRoute::subscriber($id), false),
		];
	}

	/** @return void */
	private function guard(): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}
}
