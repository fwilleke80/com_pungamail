<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\View\Archive;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;

/** Public Newsletter Archive list view. */
final class HtmlView extends BaseHtmlView
{
	/** @var array<int,object> */
	public array $items = [];
	public ?Pagination $pagination = null;
	public bool $showSentDate = true;
	public bool $showChannels = true;
	public string $intro = '';
	public bool $showPageHeading = true;
	public string $pageHeading = '';

	/** @return void */
	public function display($tpl = null): void
	{
		$data = $this->getModel()->getArchive();
		$this->items = $data['items'];
		$this->pagination = $data['pagination'];
		$params = Factory::getApplication()->getParams();
		$this->showSentDate = (int) $params->get('archive_show_sent_date', 1) === 1;
		$this->showChannels = (int) $params->get('archive_show_channels', 1) === 1;
		$this->intro = trim((string) $params->get('archive_intro', ''));
		$this->showPageHeading = (int) $params->get('show_page_heading', 1) === 1;
		$this->pageHeading = trim((string) $params->get('page_heading', ''));
		$title = trim((string) $params->get('page_title', ''));

		if ($title === '')
		{
			$title = Text::_('COM_PUNGAMAIL_ARCHIVE_PAGE_HEADING');
		}

		if ($this->pageHeading === '')
		{
			$this->pageHeading = $title;
		}

		$this->setDocumentTitle($title);
		parent::display($tpl);
	}
}
