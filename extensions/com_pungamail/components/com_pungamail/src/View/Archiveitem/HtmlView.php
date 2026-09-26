<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\View\Archiveitem;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/** Public detail view for an archived immutable newsletter snapshot. */
final class HtmlView extends BaseHtmlView
{
	public ?object $newsletter = null;

	/** @return void */
	public function display($tpl = null): void
	{
		$this->newsletter = $this->getModel()->getNewsletter();

		if ($this->newsletter === null)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ARCHIVE_NOT_FOUND'), 404);
		}

		$title = trim((string) $this->newsletter->snapshot_subject);
		if ($title === '')
		{
			$title = trim((string) $this->newsletter->title);
		}
		if ($title === '')
		{
			$title = Text::_('COM_PUNGAMAIL_ARCHIVE_PAGE_HEADING');
		}

		$this->setDocumentTitle($title);

		// The active Archive menu item already contributes its menu pathway.
		// Adding the Newsletter title makes that Archive crumb a link back to the list.
		Factory::getApplication()->getPathway()->addItem($title);

		parent::display($tpl);
	}
}
