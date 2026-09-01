<?php
/**
 * @package     Punga.Mail
 * @subpackage  Module.Signup
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Module\PungaMailSignup\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Punga\Module\PungaMailSignup\Site\Helper\PungaMailSignupHelper;

/** Signup module dispatcher. */
final class Dispatcher extends AbstractModuleDispatcher
{
	/**
	 * Adds subscription state to the layout data.
	 *
	 * @return array<string,mixed> Layout data.
	 */
	protected function getLayoutData(): array
	{
		$data = parent::getLayoutData();
		$data['pungamail'] = PungaMailSignupHelper::getState();

		return $data;
	}
}
