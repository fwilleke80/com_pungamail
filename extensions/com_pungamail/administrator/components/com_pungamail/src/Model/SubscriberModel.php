<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel;

/**
 * Form model for adding newsletter recipients from the administrator UI.
 */
final class SubscriberModel extends FormModel
{
	/**
	 * Loads the subscriber-add form.
	 *
	 * @param array<string,mixed> $data Form data.
	 * @param bool $loadData Whether to load model state.
	 *
	 * @return Form|null Form instance.
	 */
	public function getForm($data = [], $loadData = true): ?Form
	{
		return $this->loadForm(
			'com_pungamail.subscriber',
			'subscriber',
			['control' => 'jform', 'load_data' => false]
		);
	}
}
