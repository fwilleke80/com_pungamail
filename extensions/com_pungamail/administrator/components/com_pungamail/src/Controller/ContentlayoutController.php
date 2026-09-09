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
use Punga\Component\PungaMail\Administrator\Service\ContentLayoutRepository;
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Central selected-content layout editor controller. */
final class ContentlayoutController extends BaseController
{
	/** @return void */
	public function save(): void
	{
		$this->saveAndRedirect(false);
	}

	/** @return void */
	public function save2close(): void
	{
		$this->saveAndRedirect(true);
	}

	/** @return void */
	public function reset(): void
	{
		$this->requirePermission();
		$this->requireToken();
		$sourceKey = $this->sourceKey();
		ServiceFactory::contentLayouts()->reset($sourceKey);
		$this->setRedirect(Route::_(AdministratorRoute::contentLayout($sourceKey), false), Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_RESET_DONE'));
	}

	/** @return void */
	public function cancel(): void
	{
		$this->requirePermission();
		$this->requireToken();
		$this->setRedirect(Route::_(AdministratorRoute::contentLayouts(), false));
	}

	/** @return void */
	private function saveAndRedirect(bool $close): void
	{
		$this->requirePermission();
		$this->requireToken();
		$sourceKey = $this->sourceKey();
		$input = Factory::getApplication()->getInput();

		try
		{
			if ($sourceKey !== ContentLayoutRepository::DEFAULT_KEY && $input->post->getInt('use_custom', 0) !== 1)
			{
				ServiceFactory::contentLayouts()->reset($sourceKey);
			}
			else
			{
				$layout = (string) $input->post->get('layout_markdown', '', 'raw');
				ServiceFactory::contentLayouts()->save($sourceKey, $layout);
			}

			$url = $close ? AdministratorRoute::contentLayouts() : AdministratorRoute::contentLayout($sourceKey);
			$this->setRedirect(Route::_($url, false), Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::contentLayout($sourceKey), false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return string */
	private function sourceKey(): string
	{
		$sourceKey = trim(Factory::getApplication()->getInput()->getString('source_key', ContentLayoutRepository::DEFAULT_KEY));

		if ($sourceKey === ContentLayoutRepository::DEFAULT_KEY)
		{
			return $sourceKey;
		}

		if (!isset(ServiceFactory::contentTypes()->getTypes()[$sourceKey]))
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_UNKNOWN_TYPE'), 404);
		}

		return $sourceKey;
	}

	/** @return void */
	private function requirePermission(): void
	{
		if (!Permissions::can(Permissions::MANAGE_DESIGN))
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
