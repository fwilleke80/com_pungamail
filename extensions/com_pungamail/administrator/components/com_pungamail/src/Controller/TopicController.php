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
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Mailing-topic editor actions. */
final class TopicController extends BaseController
{
	/** @return void */
	public function add(): void
	{
		$this->guard(false);
		$this->setRedirect(Route::_(AdministratorRoute::topic(), false));
	}

	/** @return void */
	public function save(): void
	{
		$this->guard(true);

		try
		{
			$id = $this->saveFromInput();
			$this->setRedirect(Route::_(AdministratorRoute::topic($id), false), Text::_('COM_PUNGAMAIL_TOPIC_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::topics(), false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function save2close(): void
	{
		$this->guard(true);

		try
		{
			$id = $this->saveFromInput();
			$this->checkin($id);
			$this->setRedirect(Route::_(AdministratorRoute::topics(), false), Text::_('COM_PUNGAMAIL_TOPIC_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::topics(), false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function cancel(): void
	{
		$this->guard(true);
		$this->checkin(Factory::getApplication()->getInput()->getInt('id'));
		$this->setRedirect(Route::_(AdministratorRoute::topics(), false));
	}

	/** @return int */
	private function saveFromInput(): int
	{
		$input = Factory::getApplication()->getInput();
		$id = $input->getInt('id');
		$userId = (int) Factory::getApplication()->getIdentity()->id;

		if ($id > 0)
		{
			ServiceFactory::checkouts()->checkout('topic', $id, $userId);
		}

		$id = ServiceFactory::topics()->save(
			$id,
			$input->post->getString('title'),
			$input->post->getString('alias'),
			(string) $input->post->get('description', '', 'raw'),
			$userId,
			$input->post->getCmd('audience_mode', 'everyone'),
			(array) $input->post->get('group_ids', [], 'array')
		);
		ServiceFactory::checkouts()->checkout('topic', $id, $userId);

		return $id;
	}

	/** @return void */
	private function checkin(int $id): void
	{
		ServiceFactory::checkouts()->checkin('topic', $id, (int) Factory::getApplication()->getIdentity()->id);
	}

	/** @return void */
	private function guard(bool $token): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if ($token && !Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}
}
