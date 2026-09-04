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
use Punga\Component\PungaMail\Administrator\Service\DigestSchedule;
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Digest automation editor actions. */
final class DigestController extends BaseController
{
	/** @return void */
	public function add(): void
	{
		$this->guard(false);
		Factory::getApplication()->setUserState('com_pungamail.edit.digest.data', null);
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
		Factory::getApplication()->setUserState('com_pungamail.edit.digest.data', null);
		$this->checkin((int) Factory::getApplication()->getInput()->getInt('id', 0));
		$this->setRedirect(Route::_(AdministratorRoute::digests(), false));
	}

	/** @return void */
	private function persist(bool $close): void
	{
		$this->guard(true);
		$app = Factory::getApplication();
		$id = max(0, (int) $app->getInput()->getInt('id', 0));

		try
		{
			$data = $this->readInput();
			$app->setUserState('com_pungamail.edit.digest.data', ['id' => $id] + $data);
			$id = $this->saveData($id, $data);
			$app->setUserState('com_pungamail.edit.digest.data', null);

			if ($close)
			{
				$this->checkin($id);
			}

			$url = $close ? AdministratorRoute::digests() : AdministratorRoute::digest($id);
			$this->setRedirect(Route::_($url, false), Text::_('COM_PUNGAMAIL_DIGEST_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::digest($id), false), ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return array<string,mixed> */
	private function readInput(): array
	{
		$app = Factory::getApplication();
		$input = $app->getInput();
		$categories = [];

		foreach ((array) $input->post->get('source_categories', [], 'array') as $sourceKey => $value)
		{
			$categories[(string) $sourceKey] = array_map('intval', preg_split('/\s*,\s*/', (string) $value) ?: []);
		}

		$recurrenceValue = DigestSchedule::normalizeValue($input->post->getInt('recurrence_value', 1));
		$recurrenceUnit = DigestSchedule::normalizeUnit($input->post->getCmd('recurrence_unit', DigestSchedule::UNIT_WEEKS));

		return [
			'title' => $input->post->getString('title'),
			'state' => $input->post->getInt('state', 1),
			'template_id' => $input->post->getInt('template_id', 0),
			'subject_pattern' => $input->post->getString('subject_pattern'),
			'recurrence_value' => $recurrenceValue,
			'recurrence_unit' => $recurrenceUnit,
			'recurrence_minutes' => DigestSchedule::legacyMinutes($recurrenceValue, $recurrenceUnit),
			'next_run_at' => $this->utcDate($input->post->getString('next_run_at')),
			'cutoff_mode' => $input->post->getCmd('cutoff_mode', 'since_last'),
			'rolling_hours' => max(1, $input->post->getInt('rolling_days', 7)) * 24,
			'include_subscribers' => $input->post->getInt('include_subscribers', 0),
			'generation_mode' => $input->post->getCmd('generation_mode', 'draft'),
			'empty_action' => $input->post->getCmd('empty_action', 'skip'),
			'source_keys' => (array) $input->post->get('source_keys', [], 'array'),
			'topic_ids' => (array) $input->post->get('topic_ids', [], 'array'),
			'group_ids' => (array) $input->post->get('group_ids', [], 'array'),
			'categories' => $categories,
			'confirm_auto_send' => $input->post->getInt('confirm_auto_send', 0),
		];
	}

	/** @return int */
	private function saveData(int $id, array $data): int
	{
		$app = Factory::getApplication();
		$userId = (int) $app->getIdentity()->id;
		$existing = $id > 0 ? ServiceFactory::digests()->find($id) : null;
		$mode = (string) ($data['generation_mode'] ?? 'draft');

		if ($mode === 'auto'
			&& ($existing === null || (string) $existing->generation_mode !== 'auto')
			&& (int) ($data['confirm_auto_send'] ?? 0) !== 1)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_CONFIRM_AUTO_SEND'));
		}

		if ($id > 0)
		{
			ServiceFactory::checkouts()->checkout('digest', $id, $userId);
		}

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
