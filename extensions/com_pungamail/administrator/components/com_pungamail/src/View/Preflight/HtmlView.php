<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Preflight;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Newsletter send-preflight view.
 */
final class HtmlView extends BaseHtmlView
{
	public array $data = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException('Not authorised.', 403);
		}

		$this->data = $this->getModel()->getData();
		ToolbarHelper::title('Newsletter preflight', 'check');
		parent::display($tpl);
	}
}
