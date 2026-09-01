<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\View\Message;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/** Public subscription status view. */
final class HtmlView extends BaseHtmlView
{
	public string $type = 'requested';

	/** @return void */
	public function display($tpl = null): void
	{
		$this->type = $this->getModel()->getType();
		parent::display($tpl);
	}
}
