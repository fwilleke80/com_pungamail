<?php
/** @package Punga.Mail @subpackage Administrator.View */
namespace Punga\Component\PungaMail\Administrator\View\Digestpreview;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Service\Permissions;

/** Browser preview of the next Automatic Newsletter. */
final class HtmlView extends BaseHtmlView
{
	public array $data = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Permissions::can(Permissions::MANAGE_AUTOMATIC))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
		$this->data = $this->getModel()->getData();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_AUTOMATIC_PREVIEW'), 'eye');
		parent::display($tpl);
	}
}
