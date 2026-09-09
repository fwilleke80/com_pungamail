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
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;
use Punga\Component\PungaMail\Administrator\Service\Permissions;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;

/** Mail delivery, bounce and diagnostics actions. */
final class DeliveryController extends BaseController
{
	/** @return void */
	public function saveSettings(): void
	{
		$this->guardOptions();
		$input = Factory::getApplication()->getInput();
		$data = (array) $input->post->get('bounce', [], 'array');

		try
		{
			ServiceFactory::mailSettings()->save($data, (string) ($data['password'] ?? ''));
			$this->redirectToDelivery(Text::_('COM_PUNGAMAIL_BOUNCE_SETTINGS_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->redirectToDelivery(ErrorMessage::sanitize($e), 'error');
		}
	}


	/** @return void */
	public function saveOutgoingSettings(): void
	{
		$this->guardOptions();
		$data = (array) Factory::getApplication()->getInput()->post->get('smtp', [], 'array');

		try
		{
			ServiceFactory::mailSettings()->saveOutgoing($data, (string) ($data['password'] ?? ''));
			$this->redirectToDelivery(Text::_('COM_PUNGAMAIL_SMTP_SETTINGS_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->redirectToDelivery(ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function testOutgoing(): void
	{
		$this->guardOptions();
		$data = (array) Factory::getApplication()->getInput()->post->get('smtp', [], 'array');
		$email = trim((string) ($data['test_email'] ?? ''));

		try
		{
			ServiceFactory::mail()->sendConfigurationTestUsingSettings($email, $data, (string) ($data['password'] ?? ''));
			$this->redirectToDelivery(Text::sprintf('COM_PUNGAMAIL_MAIL_TEST_SENT', $email));
		}
		catch (\Throwable $e)
		{
			Log::add(
				'Punga Mail outgoing transport test failed: ' . get_class($e) . ': ' . ErrorMessage::sanitize($e),
				Log::ERROR,
				'com_pungamail'
			);
			$this->redirectToDelivery(Text::sprintf('COM_PUNGAMAIL_MAIL_TEST_FAILED', ErrorMessage::sanitize($e)), 'error');
		}
	}

	/** @return void */
	public function testBounce(): void
	{
		$this->guardOptions();
		$data = (array) Factory::getApplication()->getInput()->post->get('bounce', [], 'array');

		try
		{
			$result = ServiceFactory::bounces()->testConnection($data, (string) ($data['password'] ?? ''));
			$this->redirectToDelivery(ErrorMessage::sanitize($result['message']), $result['ok'] ? 'message' : 'error');
		}
		catch (\Throwable $e)
		{
			$this->redirectToDelivery(ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function processBounces(): void
	{
		$this->guard();

		try
		{
			$result = ServiceFactory::bounces()->process();
			$this->redirectToDelivery(Text::sprintf('COM_PUNGAMAIL_BOUNCE_RESULT', $result['processed'], $result['hard'], $result['soft'], $result['unknown'], $result['suppressed']));
		}
		catch (\Throwable $e)
		{
			$this->redirectToDelivery(ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function sendTest(): void
	{
		try
		{
			$this->guard();
			$email = trim(Factory::getApplication()->getInput()->post->getString('test_email'));
			ServiceFactory::mail()->sendConfigurationTest($email);
			$this->redirectToDelivery(Text::sprintf('COM_PUNGAMAIL_MAIL_TEST_SENT', $email));
		}
		catch (\Throwable $e)
		{
			Log::add(
				'Punga Mail configuration test failed: ' . get_class($e) . ': ' . ErrorMessage::sanitize($e),
				Log::ERROR,
				'com_pungamail'
			);
			$detail = ErrorMessage::sanitize($e);

			if ($detail === '')
			{
				$detail = Text::_('COM_PUNGAMAIL_UNKNOWN_MAIL_ERROR');
			}

			$this->redirectToDelivery(Text::sprintf('COM_PUNGAMAIL_MAIL_TEST_FAILED', $detail), 'error');
		}
	}

	/** @return void */
	public function retryQueue(): void
	{
		$this->guard();

		try
		{
			$count = ServiceFactory::queue()->retryFailed($this->selectedQueueIds());
			$this->redirectToDelivery(Text::plural('COM_PUNGAMAIL_QUEUE_RETRIED', $count));
		}
		catch (\Throwable $e)
		{
			$this->redirectToDelivery(ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function cancelQueue(): void
	{
		$this->guard();

		try
		{
			$count = ServiceFactory::queue()->cancel($this->selectedQueueIds());
			$this->redirectToDelivery(Text::plural('COM_PUNGAMAIL_QUEUE_ENTRIES_CANCELLED', $count));
		}
		catch (\Throwable $e)
		{
			$this->redirectToDelivery(ErrorMessage::sanitize($e), 'error');
		}
	}

	/** @return void */
	public function toggleQueue(): void
	{
		$this->guard();
		$db = ServiceFactory::database();
		$type = 'component';
		$element = 'com_pungamail';
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = :type')
			->where($db->quoteName('element') . ' = :element')
			->bind(':type', $type)
			->bind(':element', $element);
		$params = new Registry((string) $db->setQuery($query)->loadResult());
		$paused = Factory::getApplication()->getInput()->post->getInt('paused', 0) === 1;
		$params->set('queue_paused', $paused ? 1 : 0);
		$json = $params->toString();
		$update = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('params') . ' = :params')
			->where($db->quoteName('type') . ' = :type')
			->where($db->quoteName('element') . ' = :element')
			->bind(':params', $json)
			->bind(':type', $type)
			->bind(':element', $element);
		$db->setQuery($update)->execute();
		$this->redirectToDelivery(Text::_($paused ? 'COM_PUNGAMAIL_QUEUE_PAUSED_NOTICE' : 'COM_PUNGAMAIL_QUEUE_RESUMED'));
	}

	/** @return array<int,int> */
	private function selectedQueueIds(): array
	{
		$ids = array_values(array_unique(array_filter(array_map(
			'intval',
			(array) Factory::getApplication()->getInput()->post->get('queue_ids', [], 'array')
		))));

		if ($ids === [])
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_QUEUE_SELECT_ENTRIES'));
		}

		return $ids;
	}

	/** @return void */
	private function guardOptions(): void
	{
		if (!Permissions::canConfigure())
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}

	/** @return void */
	private function guard(): void
	{
		if (!Permissions::can(Permissions::MANAGE_DELIVERY))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}

	/** @return void */
	private function redirectToDelivery(string $message, string $type = 'message'): void
	{
		$return = Factory::getApplication()->getInput()->post->getCmd('pungamail_return');
		$url = $return === 'options'
			? 'index.php?option=com_config&view=component&component=com_pungamail'
			: 'index.php?option=com_pungamail&view=delivery';

		if ($return !== 'options')
		{
			$input = Factory::getApplication()->getInput();
			$filters = [
				'queue_status' => $input->post->getCmd('queue_status'),
				'queue_newsletter' => $input->post->getInt('queue_newsletter'),
				'queue_search' => trim($input->post->getString('queue_search')),
			];

			foreach ($filters as $key => $value)
			{
				if ($value !== '' && $value !== 0)
				{
					$url .= '&' . rawurlencode($key) . '=' . rawurlencode((string) $value);
				}
			}
		}

		$this->setRedirect(Route::_($url, false), $message, $type);
	}
}
