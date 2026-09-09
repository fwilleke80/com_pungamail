<?php
/** @package Punga.Mail @subpackage Administrator.View */
namespace Punga\Component\PungaMail\Administrator\View\Contentlayouts;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Service\Permissions;

/** Central selected-content layout list view. */
final class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public array $legacyOverrides = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Permissions::can(Permissions::MANAGE_DESIGN))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$model = $this->getModel();
		$this->items = $model->getItems();
		$this->legacyOverrides = $model->getLegacyOverrideCounts();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_CONTENT_LAYOUTS'), 'palette');
		if (Permissions::canConfigure())
		{
			ToolbarHelper::preferences('com_pungamail');
		}
		parent::display($tpl);
	}
}
