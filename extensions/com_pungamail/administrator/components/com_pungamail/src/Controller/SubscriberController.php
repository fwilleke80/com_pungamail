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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

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
		$this->guardCreate(true);
		$app = Factory::getApplication();
		$data = (array) $app->getInput()->post->get('jform', [], 'array');
		$type = (string) ($data['recipient_type'] ?? 'email');

		try
		{
			if ($type === 'user')
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

				ServiceFactory::subscribers()->setUserPreference(
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

				ServiceFactory::subscribers()->addAdministratorExternal($email);
			}

			$this->setRedirect(Route::_(AdministratorRoute::subscribers(), false), Text::_('COM_PUNGAMAIL_SUBSCRIBER_ADDED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::subscriber(), false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function cancel(): void
	{
		$this->guardCreate(false);
		$this->setRedirect(Route::_(AdministratorRoute::subscribers(), false));
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
		$this->setRedirect(Route::_('index.php?option=com_pungamail&view=subscribers', false), Text::_('COM_PUNGAMAIL_SUBSCRIBER_SUPPRESSED'));
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
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=subscribers', false), Text::_('COM_PUNGAMAIL_ERROR_SUBSCRIBER_NOT_FOUND'), 'error');
			return;
		}

		$tokenData = ServiceFactory::tokens()->createConfirmationToken();
		$hours = max(1, (int) ComponentHelper::getParams('com_pungamail')->get('confirmation_hours', 48));
		$expires = (new Date('+' . $hours . ' hours', 'UTC'))->toSql();
		$newId = $repo->storePendingExternal((string) $subscriber->email, $tokenData['hash'], $expires, (string) ($subscriber->language ?? 'en-GB'));
		ServiceFactory::mail()->sendConfirmation((string) $subscriber->email, $tokenData['token']);
		$repo->recordEvent($newId, 'confirmation_sent', null, null, ['source' => 'administrator']);
		$this->setRedirect(Route::_('index.php?option=com_pungamail&view=subscribers', false), Text::_('COM_PUNGAMAIL_CONFIRMATION_SENT'));
	}

	/** @return void */
	public function clearBounceSuppression(): void
	{
		$this->guard();
		$id = Factory::getApplication()->getInput()->getInt('id');
		ServiceFactory::subscribers()->clearBounceSuppression($id);
		$this->setRedirect(Route::_(AdministratorRoute::subscribers(), false), Text::_('COM_PUNGAMAIL_BOUNCE_SUPPRESSION_CLEARED'));
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
