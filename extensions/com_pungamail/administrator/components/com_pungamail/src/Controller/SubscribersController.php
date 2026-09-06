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
use Punga\Component\PungaMail\Administrator\Service\SubscriberRepository;

/**
 * Bulk subscriber actions for the Joomla administrator list view.
 */
final class SubscribersController extends BaseController
{
	/**
	 * Suppresses all selected subscriber addresses.
	 *
	 * @return void
	 */
	public function unsubscribe(): void
	{
		$this->guard();
		$ids = $this->selectedIds();
		$repo = ServiceFactory::subscribers();
		$count = 0;

		foreach ($ids as $id)
		{
			if ($repo->findById($id) === null)
			{
				continue;
			}

			$repo->unsubscribe($id, 'administrator');
			$count++;
		}

		$this->setRedirect(
			Route::_('index.php?option=com_pungamail&view=audience&screen=subscribers', false),
			Text::plural('COM_PUNGAMAIL_SUBSCRIBERS_SUPPRESSED', $count)
		);
	}

	/**
	 * Sends fresh double-opt-in confirmations to selected non-active rows.
	 *
	 * @return void
	 */
	public function requestConfirmation(): void
	{
		$this->guard();
		$ids = $this->selectedIds();
		$repo = ServiceFactory::subscribers();
		$params = ComponentHelper::getParams('com_pungamail');
		$hours = max(1, (int) $params->get('confirmation_hours', 48));
		$count = 0;

		foreach ($ids as $id)
		{
			$subscriber = $repo->findById($id);

			if ($subscriber === null || (int) $subscriber->status === SubscriberRepository::STATUS_SUBSCRIBED)
			{
				continue;
			}

			$tokenData = ServiceFactory::tokens()->createConfirmationToken();
			$expires = (new Date('+' . $hours . ' hours', 'UTC'))->toSql();
			$newId = $repo->storePendingExternal(
				(string) $subscriber->email,
				$tokenData['hash'],
				$expires,
				(string) ($subscriber->language ?? 'en-GB')
			);
			ServiceFactory::mail()->sendConfirmation((string) $subscriber->email, $tokenData['token']);
			$repo->recordEvent($newId, 'confirmation_sent', null, null, ['source' => 'administrator']);
			$count++;
		}

		$this->setRedirect(
			Route::_('index.php?option=com_pungamail&view=audience&screen=subscribers', false),
			Text::plural('COM_PUNGAMAIL_CONFIRMATIONS_SENT', $count)
		);
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
