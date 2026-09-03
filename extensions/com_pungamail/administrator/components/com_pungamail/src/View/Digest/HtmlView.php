<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Digest;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/** Digest automation editor. */
final class HtmlView extends BaseHtmlView
{
	public ?object $item = null;
	public array $templates = [];
	public array $contentTypes = [];
	public array $topics = [];
	public array $groups = [];
	public array $sourceKeys = [];
	public array $topicIds = [];
	public array $groupIds = [];
	public array $categories = [];
	public array $runs = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$model = $this->getModel();
		$this->item = $model->getItem();
		$this->templates = $model->getTemplates();
		$this->contentTypes = $model->getContentTypes();
		$this->topics = $model->getTopics();
		$this->groups = $model->getGroups();
		$this->sourceKeys = $model->getSourceKeys();
		$this->topicIds = $model->getTopicIds();
		$this->groupIds = $model->getGroupIds();
		$this->categories = $model->getCategories();
		$this->runs = $model->getRuns();
		ToolbarHelper::title(Text::_((int) ($this->item->id ?? 0) > 0 ? 'COM_PUNGAMAIL_EDIT_DIGEST' : 'COM_PUNGAMAIL_NEW_DIGEST'), 'clock');
		ToolbarHelper::apply('digest.save');
		ToolbarHelper::save('digest.save2close');
		ToolbarHelper::cancel('digest.cancel');
		parent::display($tpl);
	}
}
