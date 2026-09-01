<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Subscribers;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Subscriber list view using Joomla administrator list conventions.
 */
final class HtmlView extends BaseHtmlView
{
	/** @var array<int,object> */
	public array $items = [];

	/** @var \Joomla\CMS\Pagination\Pagination */
	public $pagination;

	/** @var \Joomla\Registry\Registry */
	public $state;

	/** @var \Joomla\CMS\Form\Form */
	public $filterForm;

	/** @var array<string,mixed> */
	public array $activeFilters = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$model = $this->getModel();
		$this->items = $model->getItems();
		$this->pagination = $model->getPagination();
		$this->state = $model->getState();
		$this->filterForm = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();

		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_SUBSCRIBERS'), 'users');
		ToolbarHelper::custom('subscribers.unsubscribe', 'ban-circle', '', Text::_('COM_PUNGAMAIL_UNSUBSCRIBE_SELECTED'), true);
		ToolbarHelper::custom('subscribers.requestConfirmation', 'mail', '', Text::_('COM_PUNGAMAIL_SEND_CONFIRMATION_SELECTED'), true);
		ToolbarHelper::preferences('com_pungamail');
		parent::display($tpl);
	}
}
