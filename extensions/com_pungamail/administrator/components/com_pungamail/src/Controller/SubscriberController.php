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
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Subscriber administration actions.
 */
final class SubscriberController extends BaseController
{
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
