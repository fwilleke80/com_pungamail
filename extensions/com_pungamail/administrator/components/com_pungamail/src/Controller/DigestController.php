<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Controller
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Controller;

use DateTimeZone;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Digest automation editor actions. */
final class DigestController extends BaseController
{
	/** @return void */
	public function add(): void
	{
		$this->guard(false);
		$this->setRedirect(Route::_(AdministratorRoute::digest(), false));
	}

	/** @return void */
	public function save(): void
	{
		$this->persist(false);
	}

	/** @return void */
	public function save2close(): void
	{
		$this->persist(true);
	}

	/** @return void */
	public function cancel(): void
	{
		$this->guard(true);
		$this->checkin(Factory::getApplication()->getInput()->getInt('id'));
		$this->setRedirect(Route::_(AdministratorRoute::digests(), false));
	}

	/** @return void */
	private function persist(bool $close): void
	{
		$this->guard(true);

		try
		{
			$id = $this->saveFromInput();

			if ($close)
			{
				$this->checkin($id);
			}

			$url = $close ? AdministratorRoute::digests() : AdministratorRoute::digest($id);
			$this->setRedirect(Route::_($url, false), Text::_('COM_PUNGAMAIL_DIGEST_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::digests(), false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return int */
	private function saveFromInput(): int
	{
		$app = Factory::getApplication();
		$input = $app->getInput();
		$id = $input->getInt('id');
		$userId = (int) $app->getIdentity()->id;
		$existing = $id > 0 ? ServiceFactory::digests()->find($id) : null;
		$mode = $input->post->getCmd('generation_mode', 'draft');

		if ($mode === 'auto'
			&& ($existing === null || (string) $existing->generation_mode !== 'auto')
			&& $input->post->getInt('confirm_auto_send') !== 1)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_CONFIRM_AUTO_SEND'));
		}

		if ($id > 0)
		{
			ServiceFactory::checkouts()->checkout('digest', $id, $userId);
		}

		$categories = [];

		foreach ((array) $input->post->get('source_categories', [], 'array') as $sourceKey => $value)
		{
			$categories[(string) $sourceKey] = array_map('intval', preg_split('/\s*,\s*/', (string) $value) ?: []);
		}

		$data = [
			'title' => $input->post->getString('title'),
			'state' => $input->post->getInt('state', 1),
			'template_id' => $input->post->getInt('template_id'),
			'subject_pattern' => $input->post->getString('subject_pattern'),
			'recurrence_minutes' => $input->post->getInt('recurrence_minutes', 10080),
			'next_run_at' => $this->utcDate($input->post->getString('next_run_at')),
			'cutoff_mode' => $input->post->getCmd('cutoff_mode', 'since_last'),
			'rolling_hours' => $input->post->getInt('rolling_hours', 168),
			'include_subscribers' => $input->post->getInt('include_subscribers'),
			'generation_mode' => $mode,
			'empty_action' => $input->post->getCmd('empty_action', 'skip'),
			'source_keys' => (array) $input->post->get('source_keys', [], 'array'),
			'topic_ids' => (array) $input->post->get('topic_ids', [], 'array'),
			'group_ids' => (array) $input->post->get('group_ids', [], 'array'),
			'categories' => $categories,
		];
		$id = ServiceFactory::digests()->save($id, $data, $userId);
		ServiceFactory::checkouts()->checkout('digest', $id, $userId);

		return $id;
	}

	/** @return void */
	private function checkin(int $id): void
	{
		ServiceFactory::checkouts()->checkin('digest', $id, (int) Factory::getApplication()->getIdentity()->id);
	}

	/** @return string */
	private function utcDate(string $value): string
	{
		if (trim($value) === '')
		{
			return Factory::getDate('now', 'UTC')->toSql();
		}

		$date = Factory::getDate($value, (string) Factory::getApplication()->get('offset', 'UTC'));
		$date->setTimezone(new DateTimeZone('UTC'));

		return $date->toSql();
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
