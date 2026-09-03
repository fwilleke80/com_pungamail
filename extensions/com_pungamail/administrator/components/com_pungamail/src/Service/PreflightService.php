<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Language\Text;

/** Performs non-mutating final validation of a newsletter and its audience. */
final class PreflightService
{
	/**
	 * @param NewsletterRepository     $newsletters  Newsletter repository.
	 * @param RecipientResolver        $recipients   Recipient resolver.
	 * @param NewsletterRenderer       $renderer     Message renderer.
	 * @param ContentTypeService       $contentTypes Registered content access.
	 * @param TemplateRepository       $templates    Template repository.
	 * @param MailConfigurationService $mailConfig   Message metadata resolver.
	 */
	public function __construct(
		private readonly NewsletterRepository $newsletters,
		private readonly RecipientResolver $recipients,
		private readonly NewsletterRenderer $renderer,
		private readonly ContentTypeService $contentTypes,
		private readonly TemplateRepository $templates,
		private readonly MailConfigurationService $mailConfig
	)
	{
	}

	/** @return array<string,mixed> */
	public function analyze(int $newsletterId): array
	{
		$newsletter = $this->newsletters->find($newsletterId);

		if ($newsletter === null)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_NEWSLETTER_NOT_FOUND'));
		}

		$items = $this->newsletters->getItems($newsletterId);
		$groups = $this->newsletters->getGroupIds($newsletterId);
		$topicIds = $this->newsletters->getTopicIds($newsletterId);
		$recipientReport = $this->recipients->resolveWithReport($newsletter, $groups, $topicIds, false);
		$rendered = $this->renderer->render($newsletter, $items);
		$template = isset($newsletter->template_id) && (int) $newsletter->template_id > 0
			? $this->templates->find((int) $newsletter->template_id)
			: null;
		$replyTo = $this->mailConfig->replyTo($template, $newsletter);
		$sender = $this->mailConfig->sender();
		$browserEnabled = $this->mailConfig->browserView($template, $newsletter);
		$errors = [];
		$warnings = [];
		$checks = [];
		$this->check(trim((string) $newsletter->subject) !== '', 'subject', 'COM_PUNGAMAIL_PREFLIGHT_SUBJECT_OK', 'COM_PUNGAMAIL_PREFLIGHT_SUBJECT_MISSING', true, $checks, $errors, $warnings);
		$this->check(filter_var($sender['email'], FILTER_VALIDATE_EMAIL) !== false, 'sender', 'COM_PUNGAMAIL_PREFLIGHT_SENDER_OK', 'COM_PUNGAMAIL_PREFLIGHT_SENDER_INVALID', true, $checks, $errors, $warnings);
		$audienceConfigured = (int) $newsletter->include_subscribers === 1 || $topicIds !== [] || $groups !== [];
		$this->check($audienceConfigured, 'audience', 'COM_PUNGAMAIL_PREFLIGHT_AUDIENCE_OK', 'COM_PUNGAMAIL_PREFLIGHT_AUDIENCE_MISSING', true, $checks, $errors, $warnings);

		if ((int) $newsletter->include_subscribers === 1 && $topicIds !== [])
		{
			$allNewsletter = clone $newsletter;
			$allNewsletter->include_subscribers = 1;
			$topicNewsletter = clone $newsletter;
			$topicNewsletter->include_subscribers = 0;
			$allSubscribers = $this->recipients->resolveWithReport($allNewsletter, [], [], false)['recipients'];
			$topicSubscribers = $this->recipients->resolveWithReport($topicNewsletter, [], $topicIds, false)['recipients'];
			$topicAddresses = array_fill_keys(array_column($topicSubscribers, 'email_normalized'), true);
			$outsideTopics = array_filter($allSubscribers, static function (array $recipient) use ($topicAddresses): bool
			{
				return !isset($topicAddresses[(string) $recipient['email_normalized']]);
			});

			if ($outsideTopics !== [])
			{
				$this->issue(
					'all_subscribers_override_topics',
					Text::plural('COM_PUNGAMAIL_PREFLIGHT_ALL_TOPICS_OVERRIDE', count($outsideTopics)),
					false,
					$checks,
					$errors,
					$warnings
				);
			}
		}

		if ($replyTo['email'] !== '')
		{
			$this->check(filter_var($replyTo['email'], FILTER_VALIDATE_EMAIL) !== false, 'reply_to', 'COM_PUNGAMAIL_PREFLIGHT_REPLY_TO_OK', 'COM_PUNGAMAIL_PREFLIGHT_REPLY_TO_INVALID', true, $checks, $errors, $warnings);
		}
		else
		{
			$checks[] = ['code' => 'reply_to', 'level' => 'ok', 'message' => Text::_('COM_PUNGAMAIL_PREFLIGHT_REPLY_TO_UNUSED')];
		}

		$this->check($recipientReport['recipients'] !== [], 'recipients', 'COM_PUNGAMAIL_PREFLIGHT_RECIPIENTS_OK', 'COM_PUNGAMAIL_ERROR_NO_RECIPIENTS', true, $checks, $errors, $warnings);
		$this->check(trim((string) $rendered['html']) !== '', 'html', 'COM_PUNGAMAIL_PREFLIGHT_HTML_OK', 'COM_PUNGAMAIL_PREFLIGHT_HTML_MISSING', true, $checks, $errors, $warnings);
		$this->check(trim((string) $rendered['text']) !== '', 'text', 'COM_PUNGAMAIL_PREFLIGHT_TEXT_OK', 'COM_PUNGAMAIL_PREFLIGHT_TEXT_MISSING', true, $checks, $errors, $warnings);
		$this->check(str_contains((string) $rendered['html'], NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER), 'unsubscribe', 'COM_PUNGAMAIL_PREFLIGHT_UNSUBSCRIBE_OK', 'COM_PUNGAMAIL_PREFLIGHT_UNSUBSCRIBE_MISSING', true, $checks, $errors, $warnings);

		if ($browserEnabled)
		{
			$this->check(str_contains((string) $rendered['html'], NewsletterRenderer::BROWSER_PLACEHOLDER), 'browser', 'COM_PUNGAMAIL_PREFLIGHT_BROWSER_OK', 'COM_PUNGAMAIL_PREFLIGHT_BROWSER_MISSING', true, $checks, $errors, $warnings);
		}
		else
		{
			$checks[] = ['code' => 'browser', 'level' => 'ok', 'message' => Text::_('COM_PUNGAMAIL_PREFLIGHT_BROWSER_DISABLED')];
		}

		$currentItems = [];
		$missingItems = [];

		foreach ($items as $selection)
		{
			$item = $this->contentTypes->findItem((string) $selection->source_key, (string) $selection->source_item_id);

			if ($item === null)
			{
				$missingItems[] = (string) $selection->source_key . ':' . (string) $selection->source_item_id;
				continue;
			}

			$currentItems[] = $item;
		}

		if ($missingItems !== [])
		{
			$this->issue('content_missing', Text::sprintf('COM_PUNGAMAIL_PREFLIGHT_CONTENT_MISSING', implode(', ', $missingItems)), true, $checks, $errors, $warnings);
		}
		else
		{
			$checks[] = ['code' => 'content_missing', 'level' => 'ok', 'message' => Text::_('COM_PUNGAMAIL_PREFLIGHT_CONTENT_REFERENCES_OK')];
		}

		$access = $this->contentTypes->filterForRecipients($currentItems, $recipientReport['recipients']);

		if ($access['violations'] !== [])
		{
			$titles = array_map(static fn (array $item): string => (string) $item['title'], $access['violations']);
			$this->issue('content_access', Text::sprintf('COM_PUNGAMAIL_PREFLIGHT_CONTENT_ACCESS_BLOCKED', implode(', ', $titles)), true, $checks, $errors, $warnings);
		}
		else
		{
			$checks[] = ['code' => 'content_access', 'level' => 'ok', 'message' => Text::_('COM_PUNGAMAIL_PREFLIGHT_CONTENT_ACCESS_OK')];
		}

		$linkIssues = $this->linkIssues((string) $rendered['html']);

		if ($linkIssues !== [])
		{
			$this->issue('links', Text::sprintf('COM_PUNGAMAIL_PREFLIGHT_LINK_WARNINGS', implode(', ', $linkIssues)), false, $checks, $errors, $warnings);
		}
		else
		{
			$checks[] = ['code' => 'links', 'level' => 'ok', 'message' => Text::_('COM_PUNGAMAIL_PREFLIGHT_LINKS_OK')];
		}

		$missingAlt = $this->missingAltCount((string) $rendered['html']);

		if ($missingAlt > 0)
		{
			$this->issue('image_alt', Text::plural('COM_PUNGAMAIL_PREFLIGHT_IMAGE_ALT_MISSING', $missingAlt), false, $checks, $errors, $warnings);
		}
		else
		{
			$checks[] = ['code' => 'image_alt', 'level' => 'ok', 'message' => Text::_('COM_PUNGAMAIL_PREFLIGHT_IMAGE_ALT_OK')];
		}

		$messageBytes = strlen((string) $rendered['html']) + strlen((string) $rendered['text']);

		if ($messageBytes > 1024 * 1024)
		{
			$this->issue('message_size', Text::sprintf('COM_PUNGAMAIL_PREFLIGHT_MESSAGE_TOO_LARGE', $this->formatBytes($messageBytes)), true, $checks, $errors, $warnings);
		}
		elseif ($messageBytes > 500 * 1024)
		{
			$this->issue('message_size', Text::sprintf('COM_PUNGAMAIL_PREFLIGHT_MESSAGE_LARGE', $this->formatBytes($messageBytes)), false, $checks, $errors, $warnings);
		}
		else
		{
			$checks[] = ['code' => 'message_size', 'level' => 'ok', 'message' => Text::sprintf('COM_PUNGAMAIL_PREFLIGHT_MESSAGE_SIZE_OK', $this->formatBytes($messageBytes))];
		}

		if ($this->mailConfig->globallyPaused() || (int) ($newsletter->queue_paused ?? 0) === 1)
		{
			$this->issue('queue', Text::_('COM_PUNGAMAIL_PREFLIGHT_QUEUE_PAUSED'), false, $checks, $errors, $warnings);
		}
		else
		{
			$checks[] = ['code' => 'queue', 'level' => 'ok', 'message' => Text::_('COM_PUNGAMAIL_PREFLIGHT_QUEUE_READY')];
		}

		return [
			'newsletter' => $newsletter,
			'items' => $items,
			'groups' => $groups,
			'topic_ids' => $topicIds,
			'recipient_report' => $recipientReport,
			'recipients' => $recipientReport['recipients'],
			'excluded' => $recipientReport['excluded'],
			'rendered' => $rendered,
			'sender' => $sender,
			'reply_to' => $replyTo,
			'browser_enabled' => $browserEnabled,
			'message_bytes' => $messageBytes,
			'checks' => $checks,
			'errors' => $errors,
			'warnings' => $warnings,
			'can_send' => $errors === [],
		];
	}

	/** @return array<string,mixed> */
	public function assertSendable(int $newsletterId): array
	{
		$data = $this->analyze($newsletterId);

		if (!$data['can_send'])
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_PREFLIGHT_BLOCKED'));
		}

		return $data;
	}

	/** @return void */
	private function check(bool $passed, string $code, string $okKey, string $failKey, bool $blocking, array &$checks, array &$errors, array &$warnings): void
	{
		if ($passed)
		{
			$checks[] = ['code' => $code, 'level' => 'ok', 'message' => Text::_($okKey)];
			return;
		}

		$this->issue($code, Text::_($failKey), $blocking, $checks, $errors, $warnings);
	}

	/** @return void */
	private function issue(string $code, string $message, bool $blocking, array &$checks, array &$errors, array &$warnings): void
	{
		$level = $blocking ? 'error' : 'warning';
		$item = ['code' => $code, 'level' => $level, 'message' => $message];
		$checks[] = $item;
		$blocking ? $errors[] = $item : $warnings[] = $item;
	}

	/** @return array<int,string> */
	private function linkIssues(string $html): array
	{
		preg_match_all('/\bhref\s*=\s*["\']([^"\']*)["\']/i', $html, $matches);
		$issues = [];

		foreach ($matches[1] ?? [] as $url)
		{
			$url = html_entity_decode((string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

			if (in_array($url, [NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, NewsletterRenderer::BROWSER_PLACEHOLDER, '#'], true))
			{
				continue;
			}

			if (preg_match('/^(?:https?:\/\/|mailto:|tel:)/i', $url) !== 1)
			{
				$issues[] = $url !== '' ? $url : Text::_('COM_PUNGAMAIL_PREFLIGHT_EMPTY_LINK');
			}
			elseif (str_starts_with(strtolower($url), 'http') && filter_var($url, FILTER_VALIDATE_URL) === false)
			{
				$issues[] = $url;
			}
		}

		return array_values(array_unique($issues));
	}

	/** @return int */
	private function missingAltCount(string $html): int
	{
		preg_match_all('/<img\b[^>]*>/i', $html, $matches);
		$count = 0;

		foreach ($matches[0] ?? [] as $image)
		{
			if (preg_match('/\balt\s*=\s*["\'][^"\']*["\']/i', (string) $image) !== 1)
			{
				$count++;
			}
		}

		return $count;
	}

	/** @return string */
	private function formatBytes(int $bytes): string
	{
		return $bytes >= 1024 ? number_format($bytes / 1024, 1) . ' KiB' : $bytes . ' B';
	}
}
