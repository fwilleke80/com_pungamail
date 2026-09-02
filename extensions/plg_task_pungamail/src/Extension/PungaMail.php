<?php
/**
 * @package     Punga.Mail
 * @subpackage  Task.Plugin
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Plugin\Task\PungaMail\Extension;

use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Joomla Scheduled Tasks integration for the persistent Punga Mail queue.
 */
final class PungaMail extends CMSPlugin implements SubscriberInterface
{
	use TaskPluginTrait;

	/** @var bool Loads the plugin language automatically. */
	protected $autoloadLanguage = true;

	/**
	 * Maps Joomla task identifiers to plugin handlers.
	 *
	 * @var array<string, array<string, string>>
	 */
	protected const TASKS_MAP = [
		'pungamail.process_queue' => [
			'langConstPrefix' => 'PLG_TASK_PUNGAMAIL_PROCESS_QUEUE',
			'method' => 'processQueue',
		],
		'pungamail.newsletter_reminder' => [
			'langConstPrefix' => 'PLG_TASK_PUNGAMAIL_NEWSLETTER_REMINDER',
			'method' => 'newsletterReminder',
		],
		'pungamail.scheduled_sends' => [
			'langConstPrefix' => 'PLG_TASK_PUNGAMAIL_SCHEDULED_SENDS',
			'method' => 'scheduledSends',
		],
		'pungamail.generate_digests' => [
			'langConstPrefix' => 'PLG_TASK_PUNGAMAIL_GENERATE_DIGESTS',
			'method' => 'generateDigests',
		],
		'pungamail.process_bounces' => [
			'langConstPrefix' => 'PLG_TASK_PUNGAMAIL_PROCESS_BOUNCES',
			'method' => 'processBounces',
		],
	];

	/**
	 * Returns events handled by this plugin.
	 *
	 * @return array<string, string>
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList' => 'advertiseRoutines',
			'onExecuteTask' => 'standardRoutineHandler',
		];
	}

	/**
	 * Checks whether the configured newsletter-age reminder is due.
	 *
	 * @param ExecuteTaskEvent $event Task execution event.
	 *
	 * @return int Joomla task status code.
	 */
	private function newsletterReminder(ExecuteTaskEvent $event): int
	{
		try
		{
			$this->getApplication()->bootComponent('com_pungamail');
			$result = ServiceFactory::reminder()->process();
			$sent = (bool) ($result['sent'] ?? false);

			Log::add(
				$sent ? 'Punga Mail newsletter reminder sent.' : 'Punga Mail newsletter reminder checked; no reminder was due.',
				Log::INFO,
				'plg_task_pungamail'
			);

			return Status::OK;
		}
		catch (\Throwable $e)
		{
			Log::add('Punga Mail reminder task failed: ' . $e->getMessage(), Log::ERROR, 'plg_task_pungamail');

			return Status::KNOCKOUT;
		}
	}

	/**
	 * Processes one configured batch of queued newsletter messages.
	 *
	 * @param ExecuteTaskEvent $event Task execution event.
	 *
	 * @return int Joomla task status code.
	 */
	private function processQueue(ExecuteTaskEvent $event): int
	{
		try
		{
			$this->getApplication()->bootComponent('com_pungamail');
			$result = ServiceFactory::processor()->process();

			Log::add(
				sprintf(
					'Punga Mail queue processed: %d sent, %d failed, %d retried.',
					(int) ($result['sent'] ?? 0),
					(int) ($result['failed'] ?? 0),
					(int) ($result['retried'] ?? 0)
				),
				Log::INFO,
				'plg_task_pungamail'
			);

			return Status::OK;
		}
		catch (\Throwable $e)
		{
			Log::add('Punga Mail queue task failed: ' . $e->getMessage(), Log::ERROR, 'plg_task_pungamail');

			return Status::KNOCKOUT;
		}
	}

	/** @return int */
	private function scheduledSends(ExecuteTaskEvent $event): int
	{
		try
		{
			$this->getApplication()->bootComponent('com_pungamail');
			$result = ServiceFactory::scheduledSends()->process();
			Log::add(sprintf('Punga Mail scheduled sends: %d queued, %d failed.', (int) $result['queued'], (int) $result['failed']), Log::INFO, 'plg_task_pungamail');

			return Status::OK;
		}
		catch (\Throwable $e)
		{
			Log::add('Punga Mail scheduled-send task failed: ' . $e->getMessage(), Log::ERROR, 'plg_task_pungamail');

			return Status::KNOCKOUT;
		}
	}

	/** @return int */
	private function generateDigests(ExecuteTaskEvent $event): int
	{
		try
		{
			$this->getApplication()->bootComponent('com_pungamail');
			$result = ServiceFactory::digestProcessor()->process();
			Log::add(sprintf('Punga Mail digests: %d processed, %d failed.', (int) $result['processed'], (int) $result['failed']), Log::INFO, 'plg_task_pungamail');

			return Status::OK;
		}
		catch (\Throwable $e)
		{
			Log::add('Punga Mail digest task failed: ' . $e->getMessage(), Log::ERROR, 'plg_task_pungamail');

			return Status::KNOCKOUT;
		}
	}

	/** @return int */
	private function processBounces(ExecuteTaskEvent $event): int
	{
		try
		{
			$this->getApplication()->bootComponent('com_pungamail');
			$result = ServiceFactory::bounces()->process();
			Log::add(sprintf('Punga Mail bounces: %d processed (%d hard, %d soft).', (int) $result['processed'], (int) $result['hard'], (int) $result['soft']), Log::INFO, 'plg_task_pungamail');

			return Status::OK;
		}
		catch (\Throwable $e)
		{
			Log::add('Punga Mail bounce task failed: ' . $e->getMessage(), Log::ERROR, 'plg_task_pungamail');

			return Status::KNOCKOUT;
		}
	}
}
