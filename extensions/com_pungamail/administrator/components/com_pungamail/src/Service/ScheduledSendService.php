<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

/** Moves due scheduled newsletters into their immutable recipient queues. */
final class ScheduledSendService
{
	/**
	 * @param NewsletterRepository $newsletters Newsletter repository.
	 * @param QueueService         $queue       Queue creator.
	 */
	public function __construct(
		private readonly NewsletterRepository $newsletters,
		private readonly QueueService $queue
	)
	{
	}

	/** @return array{processed:int,queued:int,failed:int} */
	public function process(int $limit = 20): array
	{
		$result = ['processed' => 0, 'queued' => 0, 'failed' => 0];

		foreach ($this->newsletters->dueScheduled($limit) as $newsletter)
		{
			$result['processed']++;

			try
			{
				$this->queue->queue((int) $newsletter->id);
				$result['queued']++;
			}
			catch (\Throwable $e)
			{
				$this->newsletters->markSchedulingFailed((int) $newsletter->id, ErrorMessage::sanitize($e));
				$result['failed']++;
			}
		}

		return $result;
	}
}
