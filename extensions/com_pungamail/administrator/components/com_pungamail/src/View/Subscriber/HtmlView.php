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
	public ?object $item = null;
	/** @var array<int,object> */
	public array $topics = [];
	/** @var array<int,int> */
	public array $selectedTopicIds = [];
	public ?string $suppressionReason = null;

	/** @return void */
	public function display($tpl = null): void
	{
		$user = Factory::getApplication()->getIdentity();

		$model = $this->getModel();
		$this->item = $model->getItem();
		$permission = $this->item === null ? 'core.create' : 'core.edit';

		if (!$user->authorise($permission, 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->form = $model->getForm();
		$this->topics = $model->getTopics();
		$this->selectedTopicIds = $model->getSelectedTopicIds();
		$this->suppressionReason = $model->getSuppressionReason();
		ToolbarHelper::title(Text::_($this->item === null ? 'COM_PUNGAMAIL_ADD_SUBSCRIBER' : 'COM_PUNGAMAIL_EDIT_SUBSCRIBER'), 'user');
		ToolbarHelper::apply('subscriber.save');
		ToolbarHelper::save('subscriber.save2close');
		ToolbarHelper::cancel('subscriber.cancel');
		parent::display($tpl);
	}
}
