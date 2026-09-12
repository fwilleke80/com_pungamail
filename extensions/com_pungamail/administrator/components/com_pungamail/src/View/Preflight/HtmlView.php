<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Preflight;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Newsletter send-preflight view.
 */
final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $data = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->data = $this->getModel()->getData();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_PREFLIGHT'), 'check');
		ToolbarHelper::custom('newsletter.backToEditor', 'arrow-left', '', Text::_('COM_PUNGAMAIL_BACK_TO_EDITOR'), false);

		if ($this->data['recipients'] !== [] && $this->data['can_send'])
		{
			ToolbarHelper::custom(
				'newsletter.confirmQueue',
				'mail',
				'',
				Text::plural('COM_PUNGAMAIL_QUEUE_EMAILS', count($this->data['recipients'])),
				false
			);
		}

		parent::display($tpl);
	}
}
