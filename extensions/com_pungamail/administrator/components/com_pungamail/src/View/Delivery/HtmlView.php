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
	public ?object $bounceCheck = null;
	public array $bounces = [];
	public array $diagnostics = [];
	public array $queue = [];
	public array $queueNewsletters = [];
	public array $queueFilters = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$model = $this->getModel();
		$this->settings = $model->getSettings();
		$this->bounceCheck = $model->getBounceCheck();
		$this->bounces = $model->getBounces();
		$this->diagnostics = $model->getDiagnostics();
		$this->queue = $model->getQueue();
		$this->queueNewsletters = $model->getQueueNewsletters();
		$this->queueFilters = $model->getQueueFilters();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_DELIVERY_HEALTH'), 'heart');
		parent::display($tpl);
	}
}
