<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Subscriber;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Administrator form for adding a newsletter recipient.
 */
final class HtmlView extends BaseHtmlView
{
	public ?Form $form = null;

	/** @return void */
	public function display($tpl = null): void
	{
		$user = Factory::getApplication()->getIdentity();

		if (!$user->authorise('core.create', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->form = $this->getModel()->getForm();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_ADD_SUBSCRIBER'), 'user-plus');
		ToolbarHelper::save('subscriber.save');
		ToolbarHelper::cancel('subscriber.cancel');
		parent::display($tpl);
	}
}
