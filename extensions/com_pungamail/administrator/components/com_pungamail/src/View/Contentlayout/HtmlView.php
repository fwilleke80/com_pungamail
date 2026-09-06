<?php
/** @package Punga.Mail @subpackage Administrator.View */
namespace Punga\Component\PungaMail\Administrator\View\Contentlayout;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Helper\UnsavedChangesHelper;
use Punga\Component\PungaMail\Administrator\Service\ContentLayoutRepository;

/** Central selected-content layout editor view. */
final class HtmlView extends BaseHtmlView
{
	public object $item;

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		UnsavedChangesHelper::load();
		$this->item = $this->getModel()->getItem();
		ToolbarHelper::title(Text::sprintf('COM_PUNGAMAIL_EDIT_CONTENT_LAYOUT', $this->item->label), 'palette');
		ToolbarHelper::apply('contentlayout.save');
		ToolbarHelper::save('contentlayout.save2close');
		ToolbarHelper::cancel('contentlayout.cancel');
		ToolbarHelper::custom('contentlayout.reset', 'refresh', '', $this->item->source_key === ContentLayoutRepository::DEFAULT_KEY ? Text::_('COM_PUNGAMAIL_RESTORE_BUILTIN_LAYOUT') : Text::_('COM_PUNGAMAIL_USE_DEFAULT_LAYOUT'), false);
		parent::display($tpl);
	}
}
