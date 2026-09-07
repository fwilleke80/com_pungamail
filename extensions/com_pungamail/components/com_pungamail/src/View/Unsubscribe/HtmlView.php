<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\View\Unsubscribe;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/** Punga Mail unsubscribe view. */
final class HtmlView extends BaseHtmlView
{
	public ?object $subscriber = null;

	/** @return void */
	public function display($tpl = null): void
	{
		$this->subscriber = $this->getModel()->getSubscriber();
		$titleKey = $this->subscriber === null
			? 'COM_PUNGAMAIL_UNSUBSCRIBE_INVALID_TITLE'
			: 'COM_PUNGAMAIL_UNSUBSCRIBE_TITLE';

		$this->setDocumentTitle(Text::_($titleKey));
		parent::display($tpl);
	}
}
