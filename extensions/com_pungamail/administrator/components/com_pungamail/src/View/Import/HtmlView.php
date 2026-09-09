<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Import;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Service\Permissions;

/** Subscriber CSV tools view. */
final class HtmlView extends BaseHtmlView
{
	public ?array $preview = null;
	public array $topics = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Permissions::can(Permissions::MANAGE_TOOLS))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$model = $this->getModel();
		$this->preview = $model->getPreview();
		$this->topics = $model->getTopics();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_IMPORT_EXPORT'), 'upload');
		if (Permissions::canConfigure())
		{
			ToolbarHelper::preferences('com_pungamail');
		}
		parent::display($tpl);
	}
}
