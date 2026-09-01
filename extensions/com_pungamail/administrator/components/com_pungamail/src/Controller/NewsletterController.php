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
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Newsletter editor and send-workflow controller.
 */
final class NewsletterController extends BaseController
{
	/** @return void */
	public function add(): void
	{
		$this->requirePermission('core.create');
		$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletter', false));
	}

	/** @return void */
	public function save(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$id = $this->saveFromInput();
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletter&id=' . $id, false), Text::_('COM_PUNGAMAIL_NEWSLETTER_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/**
	 * Saves the draft and reloads the editor with the selected content cutoff.
	 *
	 * @return void
	 */
	public function applyCutoff(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$id = $this->saveFromInput();
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletter&id=' . $id, false), Text::_('COM_PUNGAMAIL_CONTENT_DATE_APPLIED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/**
	 * Saves a draft and opens a rendered preview without resolving recipients.
	 *
	 * @return void
	 */
	public function preview(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$id = $this->saveFromInput();
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=preview&id=' . $id, false));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/**
	 * Saves a draft and opens the exact-recipient preflight screen.
	 *
	 * @return void
	 */
	public function preflight(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$id = $this->saveFromInput();
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=preflight&id=' . $id, false));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/**
	 * Saves and sends a test message to the current administrator.
	 *
	 * @return void
	 */
	public function sendTest(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$id = $this->saveFromInput();
			$repo = ServiceFactory::newsletters();
			$newsletter = $repo->find($id);
			$rendered = ServiceFactory::renderer()->render($newsletter, $repo->getItems($id));
			$email = (string) Factory::getApplication()->getIdentity()->email;

			if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_TEST_EMAIL'));
			}

			ServiceFactory::mail()->sendTest($email, $rendered['subject'], $rendered['html'], $rendered['text']);
			$this->setRedirect(
				Route::_('index.php?option=com_pungamail&view=newsletter&id=' . $id, false),
				Text::sprintf('COM_PUNGAMAIL_TEST_SENT', $email)
			);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/**
	 * Freezes and queues a newsletter after preflight approval.
	 *
	 * @return void
	 */
	public function confirmQueue(): void
	{
		$this->requireManage();
		$this->requireToken();
		$id = Factory::getApplication()->getInput()->getInt('id');

		try
		{
			$count = ServiceFactory::queue()->queue($id);
			$this->setRedirect(
				Route::_('index.php?option=com_pungamail&view=newsletters', false),
				Text::plural('COM_PUNGAMAIL_NEWSLETTER_QUEUED', $count)
			);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=preflight&id=' . $id, false), $e->getMessage(), 'error');
		}
	}

	/**
	 * Processes one queue batch manually.
	 *
	 * @return void
	 */
	public function processQueue(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$result = ServiceFactory::processor()->process();
			$message = Text::sprintf(
				'COM_PUNGAMAIL_QUEUE_RESULT',
				$result['processed'],
				$result['sent'],
				$result['retried'],
				$result['failed']
			);
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $message);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/**
	 * Duplicates any newsletter into a fresh mutable draft.
	 *
	 * @return void
	 */
	public function duplicate(): void
	{
		$this->requireManage();
		$this->requireToken();
		$input = Factory::getApplication()->getInput();
		$id = $input->getInt('id');
		$repo = ServiceFactory::newsletters();
		$newsletter = $repo->find($id);

		if ($newsletter === null)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), Text::_('COM_PUNGAMAIL_ERROR_NEWSLETTER_NOT_FOUND'), 'error');
			return;
		}

		$items = [];

		foreach ($repo->getItems($id) as $item)
		{
			$items[] = [
				'content_id' => (int) $item->content_id,
				'title_override' => (string) ($item->title_override ?? ''),
				'excerpt_override' => (string) ($item->excerpt_override ?? ''),
				'ordering' => (int) $item->ordering,
			];
		}

		$newId = $repo->saveDraft(
			0,
			Text::sprintf('COM_PUNGAMAIL_COPY_TITLE', (string) $newsletter->title),
			(string) $newsletter->subject,
			(string) $newsletter->body_markdown,
			(bool) $newsletter->include_subscribers,
			$repo->getLastContentCutoff(),
			$items,
			$repo->getGroupIds($id),
			(int) Factory::getApplication()->getIdentity()->id
		);
		$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletter&id=' . $newId, false), Text::_('COM_PUNGAMAIL_NEWSLETTER_DUPLICATED'));
	}

	/**
	 * Validates and persists editor POST data.
	 *
	 * @return int Saved newsletter ID.
	 */
	private function saveFromInput(): int
	{
		$input = Factory::getApplication()->getInput();
		$id = $input->getInt('id');
		$title = trim($input->post->getString('title'));
		$subject = trim($input->post->getString('subject'));
		$body = $input->post->get('body_markdown', '', 'raw');
		$includeSubscribers = $input->post->getInt('include_subscribers', 0) === 1;
		$contentCutoffStart = $this->parseCutoffDate($input->post->getString('content_cutoff_start'));
		$selectedIds = array_map('intval', (array) $input->post->get('selected_articles', [], 'array'));
		$titleOverrides = (array) $input->post->get('title_override', [], 'array');
		$excerptOverrides = (array) $input->post->get('excerpt_override', [], 'array');
		$orderings = (array) $input->post->get('article_ordering', [], 'array');
		$groupIds = array_map('intval', (array) $input->post->get('group_ids', [], 'array'));

		if ($title === '' || $subject === '')
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_TITLE_SUBJECT_REQUIRED'));
		}

		$items = [];

		foreach (array_values(array_unique(array_filter($selectedIds))) as $contentId)
		{
			$items[] = [
				'content_id' => $contentId,
				'title_override' => trim((string) ($titleOverrides[$contentId] ?? '')),
				'excerpt_override' => trim((string) ($excerptOverrides[$contentId] ?? '')),
				'ordering' => (int) ($orderings[$contentId] ?? 0),
			];
		}

		usort($items, static fn (array $a, array $b): int => $a['ordering'] <=> $b['ordering']);

		return ServiceFactory::newsletters()->saveDraft(
			$id,
			$title,
			$subject,
			(string) $body,
			$includeSubscribers,
			$contentCutoffStart,
			$items,
			$groupIds,
			(int) Factory::getApplication()->getIdentity()->id
		);
	}

	/**
	 * Converts the administrator-local HTML date value to a UTC SQL timestamp.
	 *
	 * @param string $value YYYY-MM-DD date from the editor.
	 *
	 * @return string|null UTC SQL timestamp or null when no date was supplied.
	 */
	private function parseCutoffDate(string $value): ?string
	{
		$value = trim($value);

		if ($value === '')
		{
			return null;
		}

		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1)
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_INVALID_CONTENT_DATE'));
		}

		$timezone = (string) Factory::getApplication()->get('offset', 'UTC');
		$date = Factory::getDate($value . ' 00:00:00', $timezone);
		$date->setTimezone(new DateTimeZone('UTC'));

		return $date->toSql();
	}

	/** @return void */
	private function requireManage(): void
	{
		$this->requirePermission('core.manage');
	}

	/** @return void */
	private function requirePermission(string $permission): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise($permission, 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/** @return void */
	private function requireToken(): void
	{
		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}
}
