<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Controller
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default administrator display controller.
 *
 * Secondary screens keep their list-view URL (`view=newsletters` or
 * `view=templates`) so Joomla's administrator sidebar keeps the corresponding
 * Punga Mail submenu expanded and selected. The `screen` parameter is mapped
 * to the actual MVC view only for component dispatch.
 */
final class DisplayController extends BaseController
{
	protected $default_view = 'dashboard';

	/**
	 * Resolve sidebar-preserving screen aliases and display the requested view.
	 *
	 * @param bool                 $cachable  Whether the view may be cached.
	 * @param array<string|int,mixed>|bool $urlparams Safe URL parameters for caching.
	 *
	 * @return BaseController
	 */
	public function display($cachable = false, $urlparams = [])
	{
		$input = Factory::getApplication()->getInput();
		$contextView = $input->getCmd('view', $this->default_view);
		$screen = $input->getCmd('screen', '');
		$screenViews = [
			'newsletters' => ['newsletter', 'preview', 'preflight'],
			'templates' => ['template', 'templatepreview'],
		];

		if ($screen !== '' && in_array($screen, $screenViews[$contextView] ?? [], true))
		{
			$input->set('view', $screen);
		}

		return parent::display($cachable, $urlparams);
	}
}
