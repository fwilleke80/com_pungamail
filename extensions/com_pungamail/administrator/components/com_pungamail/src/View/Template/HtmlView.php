<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Template;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Service\Permissions;
use Punga\Component\PungaMail\Administrator\Helper\UnsavedChangesHelper;

/** Template editor view. */
final class HtmlView extends BaseHtmlView
{
	public ?object $item = null;
	public array $styleOverrides = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Permissions::can(Permissions::MANAGE_DESIGN))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		UnsavedChangesHelper::load();
		$model = $this->getModel();
		$this->item = $model->getItem();
		$this->styleOverrides = $model->getStyleOverrides();

		ToolbarHelper::title($this->item ? Text::_('COM_PUNGAMAIL_EDIT_TEMPLATE') : Text::_('COM_PUNGAMAIL_NEW_TEMPLATE'), 'copy');
		ToolbarHelper::apply('template.save');
		ToolbarHelper::save('template.save2close');
		ToolbarHelper::cancel('template.cancel');
		ToolbarHelper::custom('template.preview', 'eye', '', Text::_('COM_PUNGAMAIL_PREVIEW'), false);

		Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineStyle(
			'#toolbar-eye { margin-inline-start: auto; }'
		);

		parent::display($tpl);
	}
}
