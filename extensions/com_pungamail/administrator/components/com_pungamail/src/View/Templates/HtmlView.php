<?php
/** @package Punga.Mail @subpackage Administrator.View */
namespace Punga\Component\PungaMail\Administrator\View\Templates;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Service\Permissions;

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
		if (!Permissions::can(Permissions::MANAGE_DESIGN)) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
		$model = $this->getModel();
		$this->items = $model->getItems(); $this->pagination = $model->getPagination(); $this->state = $model->getState(); $this->filterForm = $model->getFilterForm(); $this->activeFilters = $model->getActiveFilters();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_TEMPLATES'), 'copy');
		if (Permissions::can(Permissions::MANAGE_DESIGN)) { ToolbarHelper::addNew('template.add'); }
		if ((string) $this->state->get('filter.state') === '-2')
		{
			if (Permissions::can(Permissions::MANAGE_DESIGN)) { ToolbarHelper::publish('templates.restore', Text::_('COM_PUNGAMAIL_RESTORE'), true); }
			if (Permissions::can(Permissions::MANAGE_DESIGN)) { ToolbarHelper::deleteList(Text::_('COM_PUNGAMAIL_CONFIRM_DELETE_TEMPLATES'), 'templates.delete'); }
		}
		elseif (Permissions::can(Permissions::MANAGE_DESIGN)) { ToolbarHelper::trash('templates.trash'); }
		if (Permissions::canConfigure())
		{
			ToolbarHelper::preferences('com_pungamail');
		}
		parent::display($tpl);
	}
}
