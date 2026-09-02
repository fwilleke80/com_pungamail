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

/** Subscriber CSV tools view. */
final class HtmlView extends BaseHtmlView
{
	public ?array $preview = null;
	public array $topics = [];

	/** @return void */
	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$model = $this->getModel();
		$this->preview = $model->getPreview();
		$this->topics = $model->getTopics();
		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_IMPORT_EXPORT'), 'upload');
		parent::display($tpl);
	}
}
