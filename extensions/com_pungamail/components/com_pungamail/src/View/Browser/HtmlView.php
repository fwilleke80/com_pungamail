<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\View\Browser;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/** Public immutable newsletter browser view. */
final class HtmlView extends BaseHtmlView
{
	public ?object $newsletter = null;

	/** @return void */
	public function display($tpl = null): void
	{
		$this->newsletter = $this->getModel()->getNewsletter();
		$title = Text::_('COM_PUNGAMAIL_BROWSER_NOT_FOUND_TITLE');

		if ($this->newsletter !== null)
		{
			$title = trim((string) $this->newsletter->snapshot_subject);

			if ($title === '')
			{
				$title = trim((string) $this->newsletter->title);
			}

			if ($title === '')
			{
				$title = Text::_('COM_PUNGAMAIL_SUBSCRIPTION_PAGE_HEADING');
			}
		}

		$this->setDocumentTitle($title);
		parent::display($tpl);
	}
}
