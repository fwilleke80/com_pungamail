<?php
/** @package Punga.Mail @subpackage Administrator.View */
namespace Punga\Component\PungaMail\Administrator\View\Digests;
use Joomla\CMS\Factory; use Joomla\CMS\Language\Text; use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView; use Joomla\CMS\Toolbar\ToolbarHelper;
/** Digest automation list. */
final class HtmlView extends BaseHtmlView
{
	public array $items=[]; public $pagination; public $state; public $filterForm; public array $activeFilters=[];
	/** @return void */ public function display($tpl=null):void
	{
		$user=Factory::getApplication()->getIdentity(); if(!$user->authorise('core.manage','com_pungamail')){throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);} $model=$this->getModel(); $this->items=$model->getItems();$this->pagination=$model->getPagination();$this->state=$model->getState();$this->filterForm=$model->getFilterForm();$this->activeFilters=$model->getActiveFilters();ToolbarHelper::title(Text::_('COM_PUNGAMAIL_DIGESTS'),'clock');ToolbarHelper::addNew('digest.add');
		if((string)$this->state->get('filter.state')==='-2'){ToolbarHelper::publish('digests.restore',Text::_('COM_PUNGAMAIL_RESTORE'),true);ToolbarHelper::deleteList(Text::_('COM_PUNGAMAIL_CONFIRM_DELETE_DIGESTS'),'digests.delete');}else{ToolbarHelper::publish('digests.publish','JTOOLBAR_ENABLE',true);ToolbarHelper::unpublish('digests.unpublish','JTOOLBAR_DISABLE',true);ToolbarHelper::trash('digests.trash');}parent::display($tpl);
	}
}
