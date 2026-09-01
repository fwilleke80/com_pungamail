<?php
/** @package Punga.Mail @subpackage Administrator.Controller */
namespace Punga\Component\PungaMail\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Bulk/list actions for templates. */
final class TemplatesController extends BaseController
{
	/** @return void */ public function trash(): void { $this->setState(-2, Text::_('COM_PUNGAMAIL_TEMPLATES_TRASHED')); }
	/** @return void */ public function restore(): void { $this->setState(1, Text::_('COM_PUNGAMAIL_TEMPLATES_RESTORED')); }
	/** @return void */ public function delete(): void
	{
		$this->requirePermission('core.delete'); $this->requireToken();
		try { $count = ServiceFactory::templates()->deleteTrashed($this->selectedIds()); $this->setRedirect(Route::_('index.php?option=com_pungamail&view=templates&filter[state]=-2', false), Text::plural('COM_PUNGAMAIL_TEMPLATES_DELETED', $count)); }
		catch (\Throwable $e) { $this->setRedirect(Route::_('index.php?option=com_pungamail&view=templates&filter[state]=-2', false), $e->getMessage(), 'error'); }
	}
	/** @return void */ private function setState(int $state, string $message): void
	{
		$this->requirePermission('core.edit.state'); $this->requireToken();
		try { ServiceFactory::templates()->setState($this->selectedIds(), $state); $this->setRedirect(Route::_('index.php?option=com_pungamail&view=templates', false), $message); }
		catch (\Throwable $e) { $this->setRedirect(Route::_('index.php?option=com_pungamail&view=templates', false), $e->getMessage(), 'error'); }
	}
	/** @return array<int,int> */ private function selectedIds(): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) Factory::getApplication()->getInput()->post->get('cid', [], 'array')))));
		if ($ids === []) { throw new \InvalidArgumentException(Text::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST')); }
		return $ids;
	}
	/** @return void */ private function requirePermission(string $permission): void { if (!Factory::getApplication()->getIdentity()->authorise($permission, 'com_pungamail')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); } }
	/** @return void */ private function requireToken(): void { if (!Session::checkToken()) { throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403); } }
}
