<?php
/** @package Punga.Mail @subpackage Administrator.View */
namespace Punga\Component\PungaMail\Administrator\View\Topic;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/** Mailing-topic editor view. */
final class HtmlView extends BaseHtmlView
{
	public ?object $item = null;
	public array $groups = [];
	public array $selectedGroupIds = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->item = $this->getModel()->getItem();
		$this->groups = \Punga\Component\PungaMail\Administrator\Service\ServiceFactory::newsletters()->getUserGroups();
		$this->selectedGroupIds = $this->item !== null ? \Punga\Component\PungaMail\Administrator\Service\ServiceFactory::topics()->getTopicGroupIds((int) $this->item->id) : [];
		ToolbarHelper::title(Text::_($this->item ? 'COM_PUNGAMAIL_EDIT_TOPIC' : 'COM_PUNGAMAIL_NEW_TOPIC'), 'list');
		ToolbarHelper::apply('topic.save');
		ToolbarHelper::save('topic.save2close');
		ToolbarHelper::cancel('topic.cancel');
		parent::display($tpl);
	}
}
