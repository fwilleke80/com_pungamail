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
use Punga\Component\PungaMail\Administrator\Service\RecipientName;
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
		$this->setRedirect(Route::_(AdministratorRoute::newsletter(), false));
	}

	/** @return void */
	public function save(): void
	{
		$this->persistAndRedirect('COM_PUNGAMAIL_NEWSLETTER_SAVED');
	}

	/** @return void */
	public function save2close(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$this->saveFromInput();
			$this->setRedirect(Route::_(AdministratorRoute::newsletters(), false), Text::_('COM_PUNGAMAIL_NEWSLETTER_SAVED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::newsletters(), false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function cancel(): void
	{
		$this->requireManage();
		$this->setRedirect(Route::_(AdministratorRoute::newsletters(), false));
	}

	/** @return void */
	public function applyCutoff(): void
	{
		$this->persistAndRedirect('COM_PUNGAMAIL_CONTENT_DATE_APPLIED', false);
	}

	/**
	 * Applies a reusable template by copying its subject/body/style into the draft.
	 *
	 * @return void
	 */
	public function applyTemplate(): void
	{
		$this->requireManage();
		$this->requireToken();
		$input = Factory::getApplication()->getInput();
		$templateId = $input->post->getInt('template_id');
		$template = ServiceFactory::templates()->find($templateId);

		if ($template === null || (int) $template->state !== 1)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), Text::_('COM_PUNGAMAIL_ERROR_TEMPLATE_NOT_FOUND'), 'error');
			return;
		}

		try
		{
			$data = $this->readInput();
			$data['subject'] = (string) $template->subject;
			$data['body'] = (string) $template->body_markdown;
			$data['template_id'] = (int) $template->id;
			$data['style_overrides'] = $template->style_overrides !== null ? (string) $template->style_overrides : null;
			$data['custom_css'] = (string) ($template->custom_css ?? '');

			if ($data['title'] === '')
			{
				$data['title'] = (string) $template->title;
			}

			$id = $this->saveData($data, false);
			$this->setRedirect(Route::_(AdministratorRoute::newsletter($id), false), Text::_('COM_PUNGAMAIL_TEMPLATE_APPLIED'));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function preview(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$id = $this->saveFromInput();
			$this->setRedirect(Route::_(AdministratorRoute::preview($id), false));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function preflight(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$id = $this->saveFromInput();
			$this->setRedirect(Route::_(AdministratorRoute::preflight($id), false));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function sendTest(): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$id = $this->saveFromInput();
			$repo = ServiceFactory::newsletters();
			$newsletter = $repo->find($id);
			$renderer = ServiceFactory::renderer();
			$rendered = $renderer->render($newsletter, $repo->getItems($id));
			$identity = Factory::getApplication()->getIdentity();
			$email = (string) $identity->email;

			if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_TEST_EMAIL'));
			}

			$personalized = $renderer->personalize(
				$rendered['subject'],
				$rendered['html'],
				$rendered['text'],
				RecipientName::resolve((string) $identity->name, $email)
			);
			ServiceFactory::mail()->sendTest($email, $personalized['subject'], $personalized['html'], $personalized['text']);
			$this->setRedirect(Route::_(AdministratorRoute::newsletter($id), false), Text::sprintf('COM_PUNGAMAIL_TEST_SENT', $email));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function confirmQueue(): void
	{
		$this->requireManage();
		$this->requireToken();
		$id = Factory::getApplication()->getInput()->getInt('id');

		try
		{
			$count = ServiceFactory::queue()->queue($id);
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), Text::plural('COM_PUNGAMAIL_NEWSLETTER_QUEUED', $count));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_(AdministratorRoute::preflight($id), false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function processQueue(): void
	{
		$this->requireManage();
		$this->requireToken();

		$returnView = Factory::getApplication()->getInput()->post->getCmd('return') === 'dashboard' ? 'dashboard' : 'newsletters';
		$returnUrl = 'index.php?option=com_pungamail&view=' . $returnView;

		try
		{
			$result = ServiceFactory::processor()->process();
			$message = Text::sprintf('COM_PUNGAMAIL_QUEUE_RESULT', $result['processed'], $result['sent'], $result['retried'], $result['failed']);
			$this->setRedirect(Route::_($returnUrl, false), $message);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_($returnUrl, false), $e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function duplicate(): void
	{
		$this->requireManage();
		$this->requireToken();
		$id = Factory::getApplication()->getInput()->getInt('id');
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
				'source_key' => (string) $item->source_key,
				'source_item_id' => (string) $item->source_item_id,
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
			(int) Factory::getApplication()->getIdentity()->id,
			$repo->getSourceKeys($id),
			$newsletter->template_id !== null ? (int) $newsletter->template_id : null,
			$newsletter->style_overrides !== null ? (string) $newsletter->style_overrides : null,
			(string) ($newsletter->custom_css ?? '')
		);
		$this->setRedirect(Route::_(AdministratorRoute::newsletter($newId), false), Text::_('COM_PUNGAMAIL_NEWSLETTER_DUPLICATED'));
	}

	/** @return void */
	private function persistAndRedirect(string $messageKey, bool $validateRequired = true): void
	{
		$this->requireManage();
		$this->requireToken();

		try
		{
			$id = $this->saveData($this->readInput(), $validateRequired);
			$this->setRedirect(Route::_(AdministratorRoute::newsletter($id), false), Text::_($messageKey));
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=newsletters', false), $e->getMessage(), 'error');
		}
	}

	/** @return int */
	private function saveFromInput(): int
	{
		return $this->saveData($this->readInput(), true);
	}

	/** @return array<string,mixed> */
	private function readInput(): array
	{
		$input = Factory::getApplication()->getInput();
		$selectedTokens = (array) $input->post->get('selected_items', [], 'array');
		$sources = (array) $input->post->get('item_source', [], 'array');
		$itemIds = (array) $input->post->get('item_id', [], 'array');
		$titleOverrides = (array) $input->post->get('title_override', [], 'array');
		$excerptOverrides = (array) $input->post->get('excerpt_override', [], 'array');
		$orderings = (array) $input->post->get('item_ordering', [], 'array');
		$items = [];

		foreach (array_values(array_unique(array_map('strval', $selectedTokens))) as $token)
		{
			$source = trim((string) ($sources[$token] ?? ''));
			$itemId = trim((string) ($itemIds[$token] ?? ''));

			if ($source === '' || $itemId === '')
			{
				continue;
			}

			$items[] = [
				'source_key' => $source,
				'source_item_id' => $itemId,
				'title_override' => trim((string) ($titleOverrides[$token] ?? '')),
				'excerpt_override' => trim((string) ($excerptOverrides[$token] ?? '')),
				'ordering' => (int) ($orderings[$token] ?? 0),
			];
		}
		usort($items, static fn (array $a, array $b): int => $a['ordering'] <=> $b['ordering']);
		$styleInput = (array) $input->post->get('style', [], 'array');

		return [
			'id' => $input->getInt('id'),
			'title' => trim($input->post->getString('title')),
			'subject' => trim($input->post->getString('subject')),
			'body' => (string) $input->post->get('body_markdown', '', 'raw'),
			'include_subscribers' => $input->post->getInt('include_subscribers', 0) === 1,
			'cutoff' => $this->parseCutoffDate($input->post->getString('content_cutoff_start')),
			'items' => $items,
			'groups' => array_map('intval', (array) $input->post->get('group_ids', [], 'array')),
			'sources' => array_values(array_unique(array_filter(array_map('strval', (array) $input->post->get('source_keys', [], 'array'))))),
			'template_id' => $input->post->getInt('template_id') ?: null,
			'style_overrides' => ServiceFactory::styles()->encodeOverrides($styleInput),
			'custom_css' => trim((string) $input->post->get('custom_css', '', 'raw')),
		];
	}

	/** @param array<string,mixed> $data @return int */
	private function saveData(array $data, bool $validateRequired): int
	{
		if ($validateRequired && ($data['title'] === '' || $data['subject'] === ''))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_TITLE_SUBJECT_REQUIRED'));
		}

		if ($data['title'] === '')
		{
			$data['title'] = Text::_('COM_PUNGAMAIL_UNTITLED_NEWSLETTER');
		}

		return ServiceFactory::newsletters()->saveDraft(
			(int) $data['id'],
			(string) $data['title'],
			(string) $data['subject'],
			(string) $data['body'],
			(bool) $data['include_subscribers'],
			$data['cutoff'],
			$data['items'],
			$data['groups'],
			(int) Factory::getApplication()->getIdentity()->id,
			$data['sources'] !== [] ? $data['sources'] : ['com_content.article'],
			$data['template_id'],
			$data['style_overrides'],
			(string) $data['custom_css']
		);
	}

	/** @return string|null */
	private function parseCutoffDate(string $value): ?string
	{
		$value = trim($value);
		if ($value === '') { return null; }
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) { throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_ERROR_INVALID_CONTENT_DATE')); }
		$timezone = (string) Factory::getApplication()->get('offset', 'UTC');
		$date = Factory::getDate($value . ' 00:00:00', $timezone);
		$date->setTimezone(new DateTimeZone('UTC'));
		return $date->toSql();
	}

	/** @return void */
	private function requireManage(): void { $this->requirePermission('core.manage'); }

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
		if (!Session::checkToken()) { throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403); }
	}
}
