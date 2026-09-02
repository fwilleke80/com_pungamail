<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Delivery;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/** Delivery health administrator view. */
final class HtmlView extends BaseHtmlView
{
	public object $settings;
	public array $bounces = [];
	public array $diagnostics = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$model = $this->getModel();
		$this->settings = $model->getSettings();
		$this->bounces = $model->getBounces();
		$this->diagnostics = $model->getDiagnostics();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_DELIVERY_HEALTH'), 'heart');
		parent::display($tpl);
	}
}
