<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Newsletters;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Service\Permissions;

/**
 * Newsletter list view using Joomla administrator list conventions.
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
		$user = Factory::getApplication()->getIdentity();

		if (!Permissions::can('core.manage'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$model = $this->getModel();
		$this->items = $model->getItems();
		$this->pagination = $model->getPagination();
		$this->state = $model->getState();
		$this->filterForm = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();

		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_NEWSLETTERS'), 'envelope');

		if (Permissions::can('core.create'))
		{
			ToolbarHelper::addNew('newsletter.add');
			ToolbarHelper::custom('newsletters.duplicate', 'copy', '', Text::_('COM_PUNGAMAIL_DUPLICATE_AS_DRAFT'), true);
		}

		$recordState = (string) $this->state->get('filter.state');

		if ($recordState === '-2')
		{
			if (Permissions::can('core.edit.state'))
			{
				ToolbarHelper::publish('newsletters.restore', Text::_('COM_PUNGAMAIL_RESTORE'), true);
			}

			if (Permissions::can('core.delete'))
			{
				ToolbarHelper::deleteList(Text::_('COM_PUNGAMAIL_CONFIRM_DELETE_NEWSLETTERS'), 'newsletters.delete');
			}
		}
		elseif ($recordState === '2')
		{
			if (Permissions::can('core.edit.state'))
			{
				ToolbarHelper::custom('newsletters.unarchive', 'unarchive', '', Text::_('COM_PUNGAMAIL_UNARCHIVE'), true);
				ToolbarHelper::trash('newsletters.trash');
			}
		}
		elseif (Permissions::can('core.edit.state'))
		{
			ToolbarHelper::custom('newsletters.archive', 'archive', '', Text::_('COM_PUNGAMAIL_ARCHIVE'), true);
			ToolbarHelper::trash('newsletters.trash');
		}

		if (Permissions::canConfigure())
		{
			ToolbarHelper::preferences('com_pungamail');
		}
		parent::display($tpl);
	}
}
