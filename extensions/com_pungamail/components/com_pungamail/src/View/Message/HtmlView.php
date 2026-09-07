<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\View\Message;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/** Public subscription status view. */
final class HtmlView extends BaseHtmlView
{
	public string $type = 'requested';

	/** @return void */
	public function display($tpl = null): void
	{
		$this->type = $this->getModel()->getType();
		$titleKeys = [
			'requested' => 'COM_PUNGAMAIL_MESSAGE_REQUESTED_TITLE',
			'confirmed' => 'COM_PUNGAMAIL_MESSAGE_CONFIRMED_TITLE',
			'preferences_updated' => 'COM_PUNGAMAIL_MESSAGE_PREFERENCES_TITLE',
			'unsubscribed' => 'COM_PUNGAMAIL_MESSAGE_UNSUBSCRIBED_TITLE',
			'invalid_confirmation' => 'COM_PUNGAMAIL_MESSAGE_INVALID_CONFIRMATION_TITLE',
			'invalid_unsubscribe' => 'COM_PUNGAMAIL_MESSAGE_INVALID_UNSUBSCRIBE_TITLE',
		];
		$titleKey = $titleKeys[$this->type] ?? $titleKeys['requested'];

		$this->setDocumentTitle(Text::_($titleKey));
		parent::display($tpl);
	}
}
