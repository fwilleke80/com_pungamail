<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\View\Confirm;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/** Punga Mail confirm view. */
final class HtmlView extends BaseHtmlView
{
	public ?object $subscriber = null;

	/** @return void */
	public function display($tpl = null): void
	{
		$this->subscriber = $this->getModel()->getSubscriber();
		parent::display($tpl);
	}
}
