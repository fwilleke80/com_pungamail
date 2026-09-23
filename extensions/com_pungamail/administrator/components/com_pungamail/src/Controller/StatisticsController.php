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

/** Statistics maintenance actions. */
final class StatisticsController extends BaseController
{
	/** Resets Punga Mail reporting data and starts a new statistics baseline. */
	public function reset(): void
	{
		Permissions::requireConfigure();
		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}

		ServiceFactory::statistics()->reset();
		Factory::getApplication()->enqueueMessage(Text::_('COM_PUNGAMAIL_STATISTICS_RESET_DONE'), 'message');
		$this->setRedirect(Route::_('index.php?option=com_config&view=component&component=com_pungamail', false));
	}
}
