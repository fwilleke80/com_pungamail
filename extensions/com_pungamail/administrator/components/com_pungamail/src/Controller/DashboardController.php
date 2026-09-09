<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Controller
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Punga\Component\PungaMail\Administrator\Service\Permissions;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Dashboard-only administrator actions. */
final class DashboardController extends BaseController
{
	/**
	 * Acknowledges the current returned-mail suppression attention item.
	 *
	 * @return void
	 */
	public function acknowledgeBounceAttention(): void
	{
		$this->guard();
		$checkedAt = trim(Factory::getApplication()->getInput()->post->getString('checked_at'));
		$acknowledged = ServiceFactory::mailSettings()->acknowledgeBounceCheck($checkedAt);

		$this->setRedirect(
			Route::_('index.php?option=com_pungamail&view=dashboard', false),
			Text::_($acknowledged ? 'COM_PUNGAMAIL_DASHBOARD_ATTENTION_REVIEWED' : 'COM_PUNGAMAIL_DASHBOARD_ATTENTION_CHANGED'),
			$acknowledged ? 'message' : 'warning'
		);
	}

	/** @return void */
	private function guard(): void
	{
		if (!Permissions::can(Permissions::MANAGE_DELIVERY))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}
}
