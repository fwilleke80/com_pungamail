<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Newsletter;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Newsletter editor view.
 */
final class HtmlView extends BaseHtmlView
{
	public ?object $item = null;
	public array $selectedItems = [];
	public array $availableArticles = [];
	public array $queueRecipients = [];
	public array $userGroups = [];
	public array $selectedGroupIds = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException('Not authorised.', 403);
		}

		$model = $this->getModel();
		$this->item = $model->getItem();
		$this->selectedItems = $model->getSelectedItems();
		$this->availableArticles = $model->getAvailableArticles();
		$this->queueRecipients = $model->getQueueRecipients();
		$this->userGroups = $model->getUserGroups();
		$this->selectedGroupIds = $model->getSelectedGroupIds();
		ToolbarHelper::title($this->item ? 'Edit Newsletter' : 'New Newsletter', 'envelope');
		parent::display($tpl);
	}
}
