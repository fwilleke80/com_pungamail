<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Newsletters;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Newsletter list view.
 */
final class HtmlView extends BaseHtmlView
{
	/** @var array<int,object> */
	public array $items = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException('Not authorised.', 403);
		}

		$this->items = $this->getModel()->getItems();
		ToolbarHelper::title('Punga Mail', 'envelope');
		ToolbarHelper::preferences('com_pungamail');
		parent::display($tpl);
	}
}
