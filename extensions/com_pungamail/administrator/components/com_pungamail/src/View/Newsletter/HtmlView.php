<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Newsletter;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Service\RecipientName;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Newsletter editor view. */
final class HtmlView extends BaseHtmlView
{
	public ?object $item = null;
	public array $selectedItems = [];
	public array $availableContent = [];
	public array $contentTypes = [];
	public array $selectedSourceKeys = [];
	public array $templates = [];
	public array $styleOverrides = [];
	public array $queueRecipients = [];
	public array $userGroups = [];
	public array $selectedGroupIds = [];
	public array $topics = [];
	public array $selectedTopicIds = [];
	public array $statistics = [];
	public ?string $contentCutoffStart = null;

	/** @var array{subject:string,html:string,text:string}|null */
	public ?array $snapshotPreview = null;

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$model = $this->getModel();
		$this->item = $model->getItem();
		$this->selectedItems = $model->getSelectedItems();
		$this->availableContent = $model->getAvailableContent();
		$this->contentTypes = $model->getContentTypes();
		$this->selectedSourceKeys = $model->getSelectedSourceKeys();
		$this->templates = $model->getTemplates();
		$this->styleOverrides = $model->getStyleOverrides();
		$this->queueRecipients = $model->getQueueRecipients();
		$this->userGroups = $model->getUserGroups();
		$this->selectedGroupIds = $model->getSelectedGroupIds();
		$this->topics = $model->getTopics();
		$this->selectedTopicIds = $model->getSelectedTopicIds();
		$this->statistics = $model->getStatistics();
		$this->contentCutoffStart = $model->getContentCutoffStart();

		if ($this->item !== null && !empty($this->item->snapshot_html))
		{
			$identity = Factory::getApplication()->getIdentity();
			$this->snapshotPreview = ServiceFactory::renderer()->personalize(
				(string) ($this->item->snapshot_subject ?? ''),
				(string) ($this->item->snapshot_html ?? ''),
				(string) ($this->item->snapshot_text ?? ''),
				RecipientName::resolve((string) $identity->name, (string) $identity->email)
			);
		}
		ToolbarHelper::title($this->item ? Text::_('COM_PUNGAMAIL_EDIT_NEWSLETTER') : Text::_('COM_PUNGAMAIL_NEW_NEWSLETTER'), 'envelope');

		$isDraft = $this->item === null || in_array((int) $this->item->status, [
			\Punga\Component\PungaMail\Administrator\Service\NewsletterRepository::STATUS_DRAFT,
			\Punga\Component\PungaMail\Administrator\Service\NewsletterRepository::STATUS_SCHEDULED,
		], true);

		if ($isDraft)
		{
			ToolbarHelper::apply('newsletter.save');
			ToolbarHelper::save('newsletter.save2close');
			ToolbarHelper::cancel('newsletter.cancel');
			ToolbarHelper::custom('newsletter.preview', 'eye', '', Text::_('COM_PUNGAMAIL_PREVIEW'), false);
			ToolbarHelper::custom('newsletter.sendTest', 'mail', '', Text::_('COM_PUNGAMAIL_SEND_TEST_MAIL'), false);
			ToolbarHelper::custom('newsletter.preflight', 'check', '', Text::_('COM_PUNGAMAIL_REVIEW_AND_SEND'), false);

			Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineStyle(
				'#toolbar-eye { margin-inline-start: auto; }'
			);

			if ($this->item !== null && (int) $this->item->status === \Punga\Component\PungaMail\Administrator\Service\NewsletterRepository::STATUS_SCHEDULED)
			{
				ToolbarHelper::custom('newsletter.cancelScheduled', 'cancel', '', Text::_('COM_PUNGAMAIL_CANCEL_SCHEDULE'), false);
			}
		}

		parent::display($tpl);
	}
}
