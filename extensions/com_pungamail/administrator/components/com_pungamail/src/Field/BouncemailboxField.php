<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Field
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Renders securely stored bounce-mailbox settings in Component Options. */
final class BouncemailboxField extends FormField
{
	/** @var string Joomla form field type. */
	protected $type = 'Bouncemailbox';

	/**
	 * Renders the mailbox fields without binding the password to component params.
	 *
	 * @return string Mailbox configuration controls.
	 */
	protected function getInput(): string
	{
		$settings = ServiceFactory::mailSettings()->getPublic();
		$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
		$saveUrl = Route::_('index.php?option=com_pungamail&task=delivery.saveSettings');
		$testUrl = Route::_('index.php?option=com_pungamail&task=delivery.testBounce');
		$securityOptions = '';

		foreach (['ssl' => 'SSL', 'tls' => 'TLS', 'none' => Text::_('COM_PUNGAMAIL_SECURITY_NONE')] as $value => $label)
		{
			$selected = (string) $settings->bounce_security === $value ? ' selected' : '';
			$securityOptions .= '<option value="' . $value . '"' . $selected . '>' . $escape($label) . '</option>';
		}

		$passwordPlaceholder = Text::_($settings->password_configured
			? 'COM_PUNGAMAIL_PASSWORD_KEEP'
			: 'COM_PUNGAMAIL_PASSWORD_NOT_SET');
		$checked = (int) $settings->validate_cert === 1 ? ' checked' : '';
		$saveTask = "const task=this.form.querySelector('input[name=task]');task&&(task.value='delivery.saveSettings');";
		$testTask = "const task=this.form.querySelector('input[name=task]');task&&(task.value='delivery.testBounce');";

		return '<div class="alert alert-info">' . Text::_('COM_PUNGAMAIL_BOUNCE_MAILBOX_HELP') . '</div>'
			. '<div class="row g-3">'
			. '<div class="col-md-8"><label class="form-label" for="pungamail-bounce-host">' . Text::_('COM_PUNGAMAIL_SERVER') . '</label><input class="form-control" id="pungamail-bounce-host" name="bounce[bounce_host]" value="' . $escape((string) $settings->bounce_host) . '"></div>'
			. '<div class="col-md-4"><label class="form-label" for="pungamail-bounce-port">' . Text::_('COM_PUNGAMAIL_PORT') . '</label><input class="form-control" id="pungamail-bounce-port" type="number" min="1" max="65535" name="bounce[bounce_port]" value="' . (int) $settings->bounce_port . '"></div>'
			. '<div class="col-md-6"><label class="form-label" for="pungamail-bounce-security">' . Text::_('COM_PUNGAMAIL_SECURITY') . '</label><select class="form-select" id="pungamail-bounce-security" name="bounce[bounce_security]">' . $securityOptions . '</select></div>'
			. '<div class="col-md-6"><label class="form-label" for="pungamail-bounce-mailbox">' . Text::_('COM_PUNGAMAIL_MAILBOX_FOLDER') . '</label><input class="form-control" id="pungamail-bounce-mailbox" name="bounce[bounce_mailbox]" value="' . $escape((string) $settings->bounce_mailbox) . '"></div>'
			. '<div class="col-md-6"><label class="form-label" for="pungamail-bounce-user">' . Text::_('JGLOBAL_USERNAME') . '</label><input autocomplete="username" class="form-control" id="pungamail-bounce-user" name="bounce[bounce_username]" value="' . $escape((string) $settings->bounce_username) . '"></div>'
			. '<div class="col-md-6"><label class="form-label" for="pungamail-bounce-password">' . Text::_('JGLOBAL_PASSWORD') . '</label><input autocomplete="new-password" class="form-control" id="pungamail-bounce-password" type="password" name="bounce[password]" value="" placeholder="' . $escape($passwordPlaceholder) . '"></div>'
			. '<div class="col-md-8"><label class="form-label" for="pungamail-bounce-address">' . Text::_('COM_PUNGAMAIL_BOUNCE_ADDRESS') . '</label><input class="form-control" type="email" id="pungamail-bounce-address" name="bounce[bounce_address]" value="' . $escape((string) $settings->bounce_address) . '"><div class="form-text">' . Text::_('COM_PUNGAMAIL_BOUNCE_ADDRESS_HELP') . '</div></div>'
			. '<div class="col-md-4 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="bounce[validate_cert]" value="0"><input class="form-check-input" id="pungamail-validate-cert" type="checkbox" name="bounce[validate_cert]" value="1"' . $checked . '><label class="form-check-label" for="pungamail-validate-cert">' . Text::_('COM_PUNGAMAIL_VALIDATE_CERTIFICATE') . '</label></div></div>'
			. '</div><input type="hidden" name="pungamail_return" value="options">'
			. '<div class="d-flex flex-wrap gap-2 mt-3"><button class="btn btn-primary" type="submit" formaction="' . $escape($saveUrl) . '" onclick="' . $escape($saveTask) . '">' . Text::_('COM_PUNGAMAIL_SAVE_MAILBOX_SETTINGS') . '</button>'
			. '<button class="btn btn-outline-secondary" type="submit" formaction="' . $escape($testUrl) . '" onclick="' . $escape($testTask) . '">' . Text::_('COM_PUNGAMAIL_TEST_CONNECTION') . '</button></div>'
			. '<div class="form-text mt-2">' . Text::_('COM_PUNGAMAIL_BOUNCE_OPTIONS_SAVE_HELP') . '</div>';
	}
}
