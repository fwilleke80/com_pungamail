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

		if (!$user->authorise('core.manage', 'com_pungamail'))
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

		if ($user->authorise('core.create', 'com_pungamail'))
		{
			ToolbarHelper::addNew('newsletter.add');
		}

		ToolbarHelper::custom('newsletter.processQueue', 'refresh', '', Text::_('COM_PUNGAMAIL_PROCESS_QUEUE'), false);

		if ((string) $this->state->get('filter.state') === '-2')
		{
			if ($user->authorise('core.edit.state', 'com_pungamail'))
			{
				ToolbarHelper::publish('newsletters.restore', Text::_('JTOOLBAR_RESTORE'), true);
			}

			if ($user->authorise('core.delete', 'com_pungamail'))
			{
				ToolbarHelper::deleteList(Text::_('COM_PUNGAMAIL_CONFIRM_DELETE_NEWSLETTERS'), 'newsletters.delete');
			}
		}
		elseif ($user->authorise('core.edit.state', 'com_pungamail'))
		{
			ToolbarHelper::trash('newsletters.trash');
		}

		ToolbarHelper::preferences('com_pungamail');
		parent::display($tpl);
	}
}
