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
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Template editor controller. */
final class TemplateController extends BaseController
{
	/** @return void */
	public function add(): void
	{
		$this->requirePermission('core.create');
		$this->setRedirect(Route::_(AdministratorRoute::template(), false));
	}

	/** @return void */
	public function save(): void
	{
		$this->requirePermission('core.manage');
		$this->requireToken();
		try
		{
			$id = $this->saveFromInput();
			$this->setRedirect(Route::_(AdministratorRoute::template($id), false), Text::_('COM_PUNGAMAIL_TEMPLATE_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=templates', false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function save2close(): void
	{
		$this->requirePermission('core.manage');
		$this->requireToken();

		try
		{
			$this->saveFromInput();
			$this->setRedirect(Route::_(AdministratorRoute::templates(), false), Text::_('COM_PUNGAMAIL_TEMPLATE_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::templates(), false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function cancel(): void
	{
		$this->requirePermission('core.manage');
		$this->setRedirect(Route::_(AdministratorRoute::templates(), false));
	}

	/** @return void */
	public function preview(): void
	{
		$this->requirePermission('core.manage');
		$this->requireToken();
		try
		{
			$id = $this->saveFromInput();
			$this->setRedirect(Route::_(AdministratorRoute::templatePreview($id), false));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=templates', false), $e->getMessage(), 'error');
		}
	}

	/** @return int */
	private function saveFromInput(): int
	{
		$input = Factory::getApplication()->getInput();
		$title = trim($input->post->getString('title'));
		if ($title === '') { throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_TEMPLATE_TITLE_REQUIRED')); }
		$styleInput = (array) $input->post->get('style', [], 'array');
		return ServiceFactory::templates()->save(
			$input->getInt('id'),
			$title,
			trim($input->post->getString('subject')),
			(string) $input->post->get('body_markdown', '', 'raw'),
			ServiceFactory::styles()->encodeOverrides($styleInput),
			trim((string) $input->post->get('custom_css', '', 'raw')),
			(int) Factory::getApplication()->getIdentity()->id
		);
	}

	/** @return void */
	private function requirePermission(string $permission): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise($permission, 'com_pungamail')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
	}
	/** @return void */
	private function requireToken(): void { if (!Session::checkToken()) { throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403); } }
}
