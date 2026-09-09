<?php
/** @package Punga.Mail @subpackage Administrator.View */
namespace Punga\Component\PungaMail\Administrator\View\Templatepreview;
use Joomla\CMS\Factory; use Joomla\CMS\Language\Text; use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView; use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Service\Permissions;
/** Template preview view. */
final class HtmlView extends BaseHtmlView
{
	public array $data = [];
	/** @return void */ public function display($tpl = null): void
	{
		if (!Permissions::can(Permissions::MANAGE_DESIGN)) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
		$this->data = $this->getModel()->getData(); ToolbarHelper::title(Text::_('COM_PUNGAMAIL_TEMPLATE_PREVIEW'), 'eye'); parent::display($tpl);
	}
}
