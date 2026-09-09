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
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;
use Punga\Component\PungaMail\Administrator\Service\Permissions;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Bulk/list actions for newsletters.
 */
final class NewslettersController extends BaseController
{
	/** @return void */
	public function archive(): void
	{
		$this->requirePermission('core.edit.state');
		$this->requireToken();

		try
		{
			ServiceFactory::newsletters()->archive($this->selectedIds());
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), Text::_('COM_PUNGAMAIL_NEWSLETTERS_ARCHIVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function unarchive(): void
	{
		$this->setStateForSelection(1, 'core.edit.state', Text::_('COM_PUNGAMAIL_NEWSLETTERS_UNARCHIVED'), 'index.php?option=com_pungamail&view=newsletters&filter[state]=2');
	}

	/** @return void */
	public function trash(): void
	{
		$this->setStateForSelection(-2, 'core.edit.state', Text::_('COM_PUNGAMAIL_NEWSLETTERS_TRASHED'));
	}

	/** @return void */
	public function restore(): void
	{
		$this->setStateForSelection(1, 'core.edit.state', Text::_('COM_PUNGAMAIL_NEWSLETTERS_RESTORED'));
	}

	/** @return void */
	public function delete(): void
	{
		$this->requirePermission('core.delete');
		$this->requireToken();
		$ids = $this->selectedIds();

		try
		{
			$count = ServiceFactory::newsletters()->deleteTrashed($ids);
			$this->setRedirect(
				Route::_('index.php?option=com_pungamail&view=newsletters&filter[state]=-2', false),
				Text::plural('COM_PUNGAMAIL_NEWSLETTERS_DELETED', $count)
			);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters&filter[state]=-2', false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function duplicate(): void
	{
		$this->requirePermission('core.create');
		$this->requireToken();
		$application = Factory::getApplication();
		$count = 0;

		try
		{
			foreach ($this->selectedIds() as $id)
			{
				ServiceFactory::newsletters()->duplicateAsDraft($id, (int) $application->getIdentity()->id);
				$count++;
			}

			$this->setRedirect(
				Route::_('index.php?option=com_pungamail&view=newsletters', false),
				Text::plural('COM_PUNGAMAIL_NEWSLETTERS_DUPLICATED', $count)
			);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/**
	 * Applies a Joomla record state to selected newsletter rows.
	 *
	 * @param int    $state      New record state.
	 * @param string $permission Required ACL action.
	 * @param string $message    Success message.
	 *
	 * @return void
	 */
	private function setStateForSelection(int $state, string $permission, string $message, string $redirect = 'index.php?option=com_pungamail&view=newsletters'): void
	{
		$this->requirePermission($permission);
		$this->requireToken();

		try
		{
			ServiceFactory::newsletters()->setState($this->selectedIds(), $state);
			$this->setRedirect(Route::_($redirect, false), $message);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_($redirect, false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return array<int,int> */
	private function selectedIds(): array
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
	private function requirePermission(string $permission): void
	{
		if (!Permissions::can($permission))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/** @return void */
	private function requireToken(): void
	{
		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}
}
