<?php
/** @package Punga.Mail @subpackage Administrator.Controller */
namespace Punga\Component\PungaMail\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Punga\Component\PungaMail\Administrator\Service\Permissions;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Bulk/list actions for mailing topics. */
final class TopicsController extends BaseController
{
	/**
	 * Saves drag-and-drop channel ordering from Joomla's draggable list script.
	 *
	 * @return void
	 */
	public function saveOrderAjax(): void
	{
		$app = Factory::getApplication();

		try
		{
			if (!Permissions::can(Permissions::MANAGE_AUDIENCE))
			{
				throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			if (!Session::checkToken('request'))
			{
				throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
			}

			$ids = array_map('intval', (array) $app->getInput()->post->get('cid', [], 'array'));
			$orderings = array_map('intval', (array) $app->getInput()->post->get('order', [], 'array'));
			ServiceFactory::topics()->saveOrdering($ids, $orderings);
			echo new JsonResponse();
		}
		catch (\Throwable $e)
		{
			echo new JsonResponse(null, ErrorMessage::sanitize($e), true);
		}

		$app->close();
	}

	/** @return void */ public function publish(): void { $this->setState(1, 'COM_PUNGAMAIL_TOPICS_PUBLISHED'); }
	/** @return void */ public function unpublish(): void { $this->setState(0, 'COM_PUNGAMAIL_TOPICS_UNPUBLISHED'); }
	/** @return void */ public function trash(): void { $this->setState(-2, 'COM_PUNGAMAIL_TOPICS_TRASHED'); }
	/** @return void */ public function restore(): void { $this->setState(1, 'COM_PUNGAMAIL_TOPICS_RESTORED'); }

	/** @return void */
	public function delete(): void
	{
		$this->guard('core.delete');

		try
		{
			$count = ServiceFactory::topics()->deleteTrashed($this->ids());
			$this->setRedirect(Route::_(AdministratorRoute::topics() . '&filter[state]=-2', false), Text::plural('COM_PUNGAMAIL_TOPICS_DELETED', $count));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::topics(), false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	private function setState(int $state, string $message): void
	{
		$this->guard('core.edit.state');
		ServiceFactory::topics()->setState($this->ids(), $state);
		$this->setRedirect(Route::_(AdministratorRoute::topics(), false), Text::_($message));
	}

	/** @return array<int,int> */
	private function ids(): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) Factory::getApplication()->getInput()->post->get('cid', [], 'array')))));

		if ($ids === [])
		{
			throw new \InvalidArgumentException(Text::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));
		}

		return $ids;
	}

	/** @return void */
	private function guard(string $permission): void
	{
		if (!Permissions::can(Permissions::MANAGE_AUDIENCE))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}
}
