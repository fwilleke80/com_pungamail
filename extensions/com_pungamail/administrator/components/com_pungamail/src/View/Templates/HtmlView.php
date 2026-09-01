<?php
/** @package Punga.Mail @subpackage Administrator.View */
namespace Punga\Component\PungaMail\Administrator\View\Templates;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/** Joomla-standard template list view. */
final class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public $pagination;
	public $state;
	public $filterForm;
	public array $activeFilters = [];

	/** @return void */
	public function display($tpl = null): void
	{
		$user = Factory::getApplication()->getIdentity();
		if (!$user->authorise('core.manage', 'com_pungamail')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
		$model = $this->getModel();
		$this->items = $model->getItems(); $this->pagination = $model->getPagination(); $this->state = $model->getState(); $this->filterForm = $model->getFilterForm(); $this->activeFilters = $model->getActiveFilters();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_TEMPLATES'), 'copy');
		if ($user->authorise('core.create', 'com_pungamail')) { ToolbarHelper::addNew('template.add'); }
		if ((string) $this->state->get('filter.state') === '-2')
		{
			if ($user->authorise('core.edit.state', 'com_pungamail')) { ToolbarHelper::publish('templates.restore', Text::_('JTOOLBAR_RESTORE'), true); }
			if ($user->authorise('core.delete', 'com_pungamail')) { ToolbarHelper::deleteList(Text::_('COM_PUNGAMAIL_CONFIRM_DELETE_TEMPLATES'), 'templates.delete'); }
		}
		elseif ($user->authorise('core.edit.state', 'com_pungamail')) { ToolbarHelper::trash('templates.trash'); }
		parent::display($tpl);
	}
}
