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
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Bulk digest actions. */
final class DigestsController extends BaseController
{
	/** @return void */
	public function publish(): void
	{
		$this->setState(1, 'COM_PUNGAMAIL_DIGESTS_ENABLED');
	}

	/** @return void */
	public function unpublish(): void
	{
		$this->setState(0, 'COM_PUNGAMAIL_DIGESTS_DISABLED');
	}

	/** @return void */
	public function trash(): void
	{
		$this->setState(-2, 'COM_PUNGAMAIL_DIGESTS_TRASHED');
	}

	/** @return void */
	public function restore(): void
	{
		$this->setState(1, 'COM_PUNGAMAIL_DIGESTS_RESTORED');
	}

	/** @return void */
	public function delete(): void
	{
		$this->guard('core.delete');

		try
		{
			$count = ServiceFactory::digests()->deleteTrashed($this->ids());
			$this->setRedirect(
				Route::_(AdministratorRoute::digests() . '&filter[state]=-2', false),
				Text::plural('COM_PUNGAMAIL_DIGESTS_DELETED', $count)
			);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::digests(), false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	private function setState(int $state, string $message): void
	{
		$this->guard('core.edit.state');
		ServiceFactory::digests()->setState($this->ids(), $state);
		$this->setRedirect(Route::_(AdministratorRoute::digests(), false), Text::_($message));
	}

	/** @return array<int,int> */
	private function ids(): array
	{
		$ids = array_values(array_unique(array_filter(array_map(
			'intval',
			(array) Factory::getApplication()->getInput()->post->get('cid', [], 'array')
		))));

		if ($ids === [])
		{
			throw new \InvalidArgumentException(Text::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));
		}

		return $ids;
	}

	/** @return void */
	private function guard(string $permission): void
	{
		if (!Permissions::can(Permissions::MANAGE_AUTOMATIC))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}
}
