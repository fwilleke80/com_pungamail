<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Subscribers;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Subscriber list view.
 */
final class HtmlView extends BaseHtmlView
{
	public array $items = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException('Not authorised.', 403);
		}

		$this->items = $this->getModel()->getItems();
		ToolbarHelper::title('Punga Mail Subscribers', 'users');
		parent::display($tpl);
	}
}
