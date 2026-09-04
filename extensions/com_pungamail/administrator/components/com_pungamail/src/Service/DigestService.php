<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/** Generates access-safe recurring newsletters from registered Joomla content. */
final class DigestService
{
	/**
	 * @param DigestRepository     $digests      Digest repository.
	 * @param NewsletterRepository $newsletters  Newsletter repository.
	 * @param TemplateRepository   $templates    Template repository.
	 * @param ContentTypeService   $contentTypes Registered content source.
	 * @param RecipientResolver    $recipients   Recipient resolver.
	 * @param QueueService         $queue        Queue creator.
	 * @param MailService          $mail         Administrator notification sender.
	 */
	public function __construct(
		private readonly DigestRepository $digests,
		private readonly NewsletterRepository $newsletters,
		private readonly TemplateRepository $templates,
		private readonly ContentTypeService $contentTypes,
		private readonly RecipientResolver $recipients,
		private readonly QueueService $queue,
		private readonly MailService $mail
	)
	{
	}

	/** @return array{processed:int,drafts:int,queued:int,no_content:int,failed:int} */
	public function process(int $limit = 20): array
	{
		$result = ['processed' => 0, 'drafts' => 0, 'queued' => 0, 'no_content' => 0, 'failed' => 0];

		foreach ($this->digests->due($limit) as $digest)
		{
			$digestId = (int) $digest->id;

			if (!$this->digests->acquireRunLock($digestId))
			{
				continue;
			}

			$result['processed']++;
			$runId = null;

			try
			{
				// Re-check due state after locking because another worker may have
				// completed this digest between discovery and lock acquisition.
				$current = $this->digests->find($digestId);

				if ($current === null
					|| (int) $current->state !== 1
					|| (string) $current->next_run_at > (new Date('now', 'UTC'))->toSql())
				{
					$result['processed']--;
					continue;
				}

				$runId = $this->digests->startRun($digestId);
				$outcome = $this->generate($current, $runId);
				$result[$outcome]++;
			}
			catch (\Throwable $e)
			{
				if ($runId !== null)
				{
					$this->digests->finishRun($digestId, $runId, 'failed', null, 0, ErrorMessage::sanitize($e), false);
				}

				$result['failed']++;
			}
			finally
			{
				$this->digests->releaseRunLock($digestId);
			}
		}

		return $result;
	}

	/** @return string Result counter key. */
	private function generate(object $digest, int $runId): string
	{
		$template = $this->templates->find((int) $digest->template_id);

		if ($template === null || (int) $template->state !== 1)
		{
			throw new \RuntimeException('The configured digest template is unavailable.');
		}

		$now = new Date('now', 'UTC');
		$cutoff = $this->cutoff($digest, $now);
		$sourceKeys = $this->digests->getSourceKeys((int) $digest->id);
		$items = $this->contentTypes->getItems($sourceKeys, $cutoff, 1000);
		$categories = $this->digests->getCategories((int) $digest->id);

		if ($categories !== [])
		{
			$items = array_values(array_filter($items, static function (object $item) use ($categories): bool
			{
				$allowed = $categories[(string) $item->source_key] ?? [];

				return $allowed === [] || in_array((int) ($item->catid ?? 0), $allowed, true);
			}));
		}

		$topicIds = $this->digests->getTopicIds((int) $digest->id);
		$groupIds = $this->digests->getGroupIds((int) $digest->id);
		$audience = (object) ['include_subscribers' => (int) $digest->include_subscribers];
		$recipientReport = $this->recipients->resolveWithReport($audience, $groupIds, $topicIds, false);
		$access = $this->contentTypes->filterForRecipients($items, $recipientReport['recipients']);
		$items = $access['items'];
		$blockedCount = count($access['violations']);

		if ($items === [] && (string) $digest->empty_action === 'skip')
		{
			$message = $blockedCount > 0
				? $blockedCount . ' matching item(s) were excluded by Joomla access permissions.'
				: 'No matching new content was available.';
			$this->digests->finishRun((int) $digest->id, $runId, 'no_content', null, 0, $message, true);
			return 'no_content';
		}

		$forceDraftForEmptyDigest = $items === [] && (string) $digest->empty_action === 'create_draft';

		$selections = [];

		foreach ($items as $index => $item)
		{
			$selections[] = [
				'source_key' => (string) $item->source_key,
				'source_item_id' => (string) $item->id,
				'title_override' => '',
				'excerpt_override' => '',
				'ordering' => $index,
			];
		}

		$siteName = (string) Factory::getApplication()->get('sitename');
		$date = $now->format('Y-m-d', true);
		$subjectPattern = trim((string) $digest->subject_pattern) ?: (string) $template->subject;
		$subject = strtr($subjectPattern, ['{date}' => $date, '{site_name}' => $siteName]);
		$internalTitle = (string) $digest->title . ' — ' . $date;
		$newsletterId = $this->newsletters->saveDraft(
			0,
			$internalTitle,
			$subject,
			(string) $template->body_markdown,
			(int) $digest->include_subscribers === 1,
			$cutoff,
			$selections,
			$groupIds,
			(int) $digest->created_by,
			$sourceKeys,
			(int) $template->id,
			$template->style_overrides !== null ? (string) $template->style_overrides : null,
			(string) ($template->custom_css ?? ''),
			[
				'topic_ids' => $topicIds,
				'heading_mode' => (string) ($template->heading_mode ?? 'inherit'),
				'mail_heading' => (string) ($template->mail_heading ?? ''),
				'browser_view' => (int) ($template->browser_view ?? -1),
				'reply_to_mode' => (string) ($template->reply_to_mode ?? 'inherit'),
				'reply_to_email' => (string) ($template->reply_to_email ?? ''),
				'reply_to_name' => (string) ($template->reply_to_name ?? ''),
				// Generated newsletters inherit this from their Template unless explicitly overridden later.
				'new_content_item_template' => '',
			]
		);

		if ((string) $digest->generation_mode === 'auto' && !$forceDraftForEmptyDigest)
		{
			$this->queue->queue($newsletterId);
			$message = $blockedCount > 0 ? $blockedCount . ' inaccessible item(s) were excluded.' : '';
			$this->digests->finishRun((int) $digest->id, $runId, 'queued', $newsletterId, count($items), $message, true);
			return 'queued';
		}

		$message = $forceDraftForEmptyDigest
			? 'No matching content was available; an empty draft was created as configured.'
			: ($blockedCount > 0 ? $blockedCount . ' inaccessible item(s) were excluded.' : '');
		$notificationError = $this->notifyDraftCreated($digest, $internalTitle, $newsletterId, count($items), $blockedCount);

		if ($notificationError !== '')
		{
			$message = trim($message . ' ' . $notificationError);
		}

		$this->digests->finishRun((int) $digest->id, $runId, 'draft', $newsletterId, count($items), $message, true);

		return 'drafts';
	}

	/** @return string */
	private function cutoff(object $digest, Date $now): string
	{
		if ((string) $digest->cutoff_mode === 'rolling')
		{
			$date = clone $now;
			$date->modify('-' . max(1, (int) $digest->rolling_hours) . ' hours');

			return $date->toSql();
		}

		if (!empty($digest->last_cutoff_at))
		{
			return (string) $digest->last_cutoff_at;
		}

		if ((string) ($digest->recurrence_unit ?? '') === 'legacy')
		{
			$date = clone $now;
			$date->modify('-' . max(15, (int) ($digest->recurrence_minutes ?? 10080)) . ' minutes');

			return $date->toSql();
		}

		return DigestSchedule::subtract(
			$now->toSql(),
			DigestSchedule::normalizeValue((int) ($digest->recurrence_value ?? 1)),
			DigestSchedule::normalizeUnit((string) ($digest->recurrence_unit ?? DigestSchedule::UNIT_WEEKS))
		);
	}
	/**
	 * Sends the optional draft-review notification without turning a successful
	 * automatic draft creation into a failed run when mail delivery fails.
	 *
	 * @return string Empty on success/disabled, otherwise a short diagnostic.
	 */
	private function notifyDraftCreated(object $digest, string $draftTitle, int $newsletterId, int $itemCount, int $blockedCount): string
	{
		$params = ComponentHelper::getParams('com_pungamail');

		if ((int) $params->get('automatic_draft_notification_enabled', 0) !== 1)
		{
			return '';
		}

		$email = trim((string) $params->get('automatic_draft_notification_email', ''));

		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return 'Draft review notification was enabled, but no valid recipient address is configured.';
		}

		$reviewUrl = rtrim(Uri::root(), '/') . '/administrator/' . AdministratorRoute::newsletter($newsletterId);

		try
		{
			$this->mail->sendAutomaticDraftNotification(
				$email,
				(string) $digest->title,
				$draftTitle,
				$itemCount,
				$blockedCount,
				$reviewUrl
			);
		}
		catch (\Throwable $e)
		{
			return 'Draft review notification could not be sent: ' . ErrorMessage::sanitize($e);
		}

		return '';
	}

}
