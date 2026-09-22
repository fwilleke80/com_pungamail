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
	 * @param DigestRepository         $digests           Digest repository.
	 * @param NewsletterRepository     $newsletters       Newsletter repository.
	 * @param TemplateRepository       $templates         Template repository.
	 * @param ContentTypeService       $contentTypes      Registered content source.
	 * @param RecipientResolver        $recipients        Recipient resolver.
	 * @param QueueService             $queue             Queue creator.
	 * @param MailService              $mail              Mail sender.
	 * @param NewsletterRenderer       $renderer          Newsletter renderer.
	 * @param MailConfigurationService $mailConfiguration Mail metadata resolver.
	 * @param SiteDateService          $siteDate          Site-local date formatter.
	 */
	public function __construct(
		private readonly DigestRepository $digests,
		private readonly NewsletterRepository $newsletters,
		private readonly TemplateRepository $templates,
		private readonly ContentTypeService $contentTypes,
		private readonly RecipientResolver $recipients,
		private readonly QueueService $queue,
		private readonly MailService $mail,
		private readonly NewsletterRenderer $renderer,
		private readonly MailConfigurationService $mailConfiguration,
		private readonly SiteDateService $siteDate
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

	/**
	 * Sends a simulation of the next Automatic Newsletter to one administrator.
	 * No digest history, newsletter row, queue row, cutoff, or schedule state is changed.
	 *
	 * @return array{status:string,item_count:int,available_count:int,minimum_items:int,blocked_count:int,cutoff:string}
	 */
	public function sendTest(int $digestId, string $email, string $recipientName, ?int $userId = null): array
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException('A valid test recipient email address is required.');
		}

		$digest = $this->digests->find($digestId);

		if ($digest === null)
		{
			throw new \RuntimeException('The Automatic Newsletter could not be found.');
		}

		$now = new Date('now', 'UTC');
		$prepared = $this->prepare($digest, $now);
		$contentSelection = $prepared['content_selection'];
		$items = $contentSelection['items'];
		$result = [
			'status' => 'sent',
			'item_count' => count($items),
			'available_count' => $contentSelection['available_count'],
			'minimum_items' => $contentSelection['minimum_items'],
			'blocked_count' => $prepared['blocked_count'],
			'cutoff' => $prepared['cutoff'],
		];

		if ($contentSelection['below_minimum'])
		{
			$result['status'] = 'below_minimum';

			return $result;
		}

		if ($items === [] && (string) $digest->empty_action === 'skip')
		{
			$result['status'] = 'no_content';

			return $result;
		}

		$message = $this->message($digest, $prepared['template'], $now);
		$newsletter = $this->transientNewsletter($prepared['template'], $message['subject'], $message['body_markdown']);
		$rendered = $this->renderer->render($newsletter, $this->selectionObjects($items));
		$personalized = $this->renderer->personalize($rendered['subject'], $rendered['html'], $rendered['text'], $recipientName, $userId);
		$replyTo = $this->mailConfiguration->replyTo($prepared['template'], $newsletter);
		$this->mail->sendTest($email, $personalized['subject'], $personalized['html'], $personalized['text'], $replyTo['email'], $replyTo['name']);

		return $result;
	}

	/**
	 * Previews one content source using the current unsaved Automatic Newsletter settings.
	 *
	 * @param array<string,array<int,array<string,mixed>>> $filters Current source filters.
	 * @param array<int,int|string> $topicIds Current topic audience.
	 * @param array<int,int|string> $groupIds Current Joomla-group audience.
	 *
	 * @return array{count:int,blocked_count:int,cutoff:string,items:array<int,array{title:string,published:string}>}
	 */
	public function previewSource(object $digest, string $sourceKey, array $filters, array $topicIds, array $groupIds): array
	{
		$sourceKey = trim($sourceKey);

		if ($sourceKey === '' || !isset($this->contentTypes->getTypes()[$sourceKey]))
		{
			throw new \InvalidArgumentException('A valid content source is required.');
		}

		$now = new Date('now', 'UTC');
		$cutoff = $this->cutoff($digest, $now);
		$items = $this->contentTypes->getItems([$sourceKey], $cutoff, 1000);
		$items = DigestContentFilter::apply($items, DigestContentFilter::normalize($filters));
		$audience = (object) ['include_subscribers' => (int) ($digest->include_subscribers ?? 0)];
		$recipientReport = $this->recipients->resolveWithReport(
			$audience,
			array_values(array_unique(array_filter(array_map('intval', $groupIds)))),
			array_values(array_unique(array_filter(array_map('intval', $topicIds)))),
			false
		);
		$access = $this->contentTypes->filterForRecipients($items, $recipientReport['recipients']);
		$items = $access['items'];

		usort($items, static fn (object $a, object $b): int => strcmp((string) $b->published, (string) $a->published));

		return [
			'count' => count($items),
			'blocked_count' => count($access['violations']),
			'cutoff' => $cutoff,
			'items' => array_map(fn (object $item): array => [
				'title' => (string) ($item->title ?? ''),
				'published' => trim((string) ($item->published ?? '')) !== ''
					? $this->siteDate->formatDateTime(new Date((string) $item->published, 'UTC'))
					: '',
			], array_slice($items, 0, 20)),
		];
	}

	/** @return string Result counter key. */
	private function generate(object $digest, int $runId): string
	{
		$now = new Date('now', 'UTC');
		$prepared = $this->prepare($digest, $now);
		$contentSelection = $prepared['content_selection'];
		$items = $contentSelection['items'];
		$availableCount = $contentSelection['available_count'];
		$minimumItems = $contentSelection['minimum_items'];
		$blockedCount = $prepared['blocked_count'];

		if ($contentSelection['below_minimum'])
		{
			$message = $availableCount . ' matching item(s) were available; at least ' . $minimumItems . ' are required. The content cutoff was not advanced so eligible content can accumulate for the next run.';

			if ($blockedCount > 0)
			{
				$message .= ' ' . $blockedCount . ' additional item(s) were excluded by Joomla access permissions.';
			}

			$this->digests->finishRun((int) $digest->id, $runId, 'below_minimum', null, $availableCount, $message, false);
			return 'no_content';
		}

		if ($items === [] && (string) $digest->empty_action === 'skip')
		{
			$message = $blockedCount > 0
				? $blockedCount . ' matching item(s) were excluded by Joomla access permissions.'
				: 'No matching new content was available.';
			$this->digests->finishRun((int) $digest->id, $runId, 'no_content', null, 0, $message, true);
			return 'no_content';
		}

		$forceDraftForEmptyDigest = $items === [] && (string) $digest->empty_action === 'create_draft';
		$messageData = $this->message($digest, $prepared['template'], $now);
		$internalTitle = (string) $digest->title . ' — ' . $messageData['date'];
		$newsletterId = $this->newsletters->saveDraft(
			0,
			$internalTitle,
			$messageData['subject'],
			$messageData['body_markdown'],
			(int) $digest->include_subscribers === 1,
			$prepared['cutoff'],
			$this->selectionArrays($items),
			$prepared['group_ids'],
			(int) $digest->created_by,
			$prepared['source_keys'],
			(int) $prepared['template']->id,
			$prepared['template']->style_overrides !== null ? (string) $prepared['template']->style_overrides : null,
			(string) ($prepared['template']->custom_css ?? ''),
			[
				'topic_ids' => $prepared['topic_ids'],
				'heading_mode' => (string) ($prepared['template']->heading_mode ?? 'inherit'),
				'mail_heading' => (string) ($prepared['template']->mail_heading ?? ''),
				'browser_view' => (int) ($prepared['template']->browser_view ?? -1),
				'reply_to_mode' => (string) ($prepared['template']->reply_to_mode ?? 'inherit'),
				'reply_to_email' => (string) ($prepared['template']->reply_to_email ?? ''),
				'reply_to_name' => (string) ($prepared['template']->reply_to_name ?? ''),
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

	/**
	 * Builds the exact candidate set shared by real and test Automatic Newsletter runs.
	 *
	 * @return array{template:object,cutoff:string,source_keys:array<int,string>,topic_ids:array<int,int>,group_ids:array<int,int>,blocked_count:int,content_selection:array{items:array<int,object>,available_count:int,minimum_items:int,below_minimum:bool}}
	 */
	private function prepare(object $digest, Date $now): array
	{
		$template = $this->templates->find((int) $digest->template_id);

		if ($template === null || (int) $template->state !== 1)
		{
			throw new \RuntimeException('The configured digest template is unavailable.');
		}

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

		$items = DigestContentFilter::apply($items, $this->digests->getFilters((int) $digest->id));

		$topicIds = $this->digests->getTopicIds((int) $digest->id);
		$groupIds = $this->digests->getGroupIds((int) $digest->id);
		$audience = (object) ['include_subscribers' => (int) $digest->include_subscribers];
		$recipientReport = $this->recipients->resolveWithReport($audience, $groupIds, $topicIds, false);
		$access = $this->contentTypes->filterForRecipients($items, $recipientReport['recipients']);
		$contentSelection = DigestContentSelection::apply(
			$access['items'],
			(string) ($digest->content_order ?? 'newest'),
			(int) ($digest->max_items ?? 0),
			(int) ($digest->minimum_items ?? 0)
		);

		return [
			'template' => $template,
			'cutoff' => $cutoff,
			'source_keys' => $sourceKeys,
			'topic_ids' => $topicIds,
			'group_ids' => $groupIds,
			'blocked_count' => count($access['violations']),
			'content_selection' => $contentSelection,
		];
	}

	/** @return array{subject:string,body_markdown:string,date:string} */
	private function message(object $digest, object $template, Date $now): array
	{
		$siteName = (string) Factory::getApplication()->get('sitename');
		$date = $this->siteDate->format($now);
		$subjectPattern = trim((string) $digest->subject_pattern) ?: (string) $template->subject;

		return [
			'subject' => strtr($subjectPattern, ['{date}' => $date, '{site_name}' => $siteName]),
			'body_markdown' => str_replace(NewsletterRenderer::DATE_PLACEHOLDER, $date, (string) $template->body_markdown),
			'date' => $date,
		];
	}

	/** @param array<int,object> $items @return array<int,array<string,mixed>> */
	private function selectionArrays(array $items): array
	{
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

		return $selections;
	}

	/** @param array<int,object> $items @return array<int,object> */
	private function selectionObjects(array $items): array
	{
		return array_map(static fn (array $selection): object => (object) $selection, $this->selectionArrays($items));
	}

	/** @return object */
	private function transientNewsletter(object $template, string $subject, string $bodyMarkdown): object
	{
		return (object) [
			'subject' => $subject,
			'body_markdown' => $bodyMarkdown,
			'template_id' => (int) $template->id,
			'style_overrides' => $template->style_overrides !== null ? (string) $template->style_overrides : null,
			'custom_css' => (string) ($template->custom_css ?? ''),
			'heading_mode' => (string) ($template->heading_mode ?? 'inherit'),
			'mail_heading' => (string) ($template->mail_heading ?? ''),
			'browser_view' => (int) ($template->browser_view ?? -1),
			'reply_to_mode' => (string) ($template->reply_to_mode ?? 'inherit'),
			'reply_to_email' => (string) ($template->reply_to_email ?? ''),
			'reply_to_name' => (string) ($template->reply_to_name ?? ''),
		];
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
