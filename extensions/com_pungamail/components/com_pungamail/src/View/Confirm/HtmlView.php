<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\View\Confirm;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/** Punga Mail confirm view. */
final class HtmlView extends BaseHtmlView
{
	public ?object $subscriber = null;

	/** @return void */
	public function display($tpl = null): void
	{
		$this->subscriber = $this->getModel()->getSubscriber();
		$kind = Factory::getApplication()->getInput()->getCmd('kind');
		$titleKey = 'COM_PUNGAMAIL_CONFIRM_INVALID_TITLE';

		if ($this->subscriber !== null)
		{
			$titleKey = $kind === 'topics'
				? 'COM_PUNGAMAIL_CONFIRM_TOPICS_TITLE'
				: 'COM_PUNGAMAIL_CONFIRM_TITLE';
		}

		$this->setDocumentTitle(Text::_($titleKey));
		parent::display($tpl);
	}
}
