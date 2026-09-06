<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Field
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Renders Punga Mail's independent outgoing transport settings. */
final class OutgoingmailField extends FormField
{
	/** @var string Joomla form field type. */
	protected $type = 'Outgoingmail';

	/** @return string Outgoing transport controls. */
	protected function getInput(): string
	{
		$settings = ServiceFactory::mailSettings()->getOutgoingPublic();
		$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
		$saveUrl = Route::_('index.php?option=com_pungamail&task=delivery.saveOutgoingSettings');
		$testUrl = Route::_('index.php?option=com_pungamail&task=delivery.testOutgoing');
		$mode = (string) $settings->smtp_mode;
		$passwordPlaceholder = Text::_($settings->password_configured
			? 'COM_PUNGAMAIL_PASSWORD_KEEP'
			: 'COM_PUNGAMAIL_PASSWORD_NOT_SET');
		$testEmail = (string) Factory::getApplication()->getIdentity()->email;
		$securityOptions = '';

		foreach (['tls' => 'STARTTLS', 'ssl' => 'SSL/TLS', 'none' => Text::_('COM_PUNGAMAIL_SECURITY_NONE')] as $value => $label)
		{
			$selected = (string) $settings->smtp_security === $value ? ' selected' : '';
			$securityOptions .= '<option value="' . $value . '"' . $selected . '>' . $escape($label) . '</option>';
		}

		$authChecked = (int) $settings->smtp_auth === 1 ? ' checked' : '';
		$customHidden = $mode === 'custom' ? '' : ' hidden';
		$saveTask = "const task=this.form.querySelector('input[name=task]');task&&(task.value='delivery.saveOutgoingSettings');";
		$testTask = "const task=this.form.querySelector('input[name=task]');task&&(task.value='delivery.testOutgoing');";

		$html = '<div class="alert alert-info">' . Text::_('COM_PUNGAMAIL_OUTGOING_MAIL_HELP') . '</div>';
		$html .= '<div class="row g-3">';
		$html .= '<div class="col-12"><label class="form-label" for="pungamail-smtp-mode">' . Text::_('COM_PUNGAMAIL_OUTGOING_TRANSPORT') . '</label>';
		$html .= '<select class="form-select" id="pungamail-smtp-mode" name="smtp[smtp_mode]">';
		$html .= '<option value="joomla"' . ($mode === 'joomla' ? ' selected' : '') . '>' . Text::_('COM_PUNGAMAIL_USE_JOOMLA_MAIL_SETTINGS') . '</option>';
		$html .= '<option value="custom"' . ($mode === 'custom' ? ' selected' : '') . '>' . Text::_('COM_PUNGAMAIL_CUSTOM_SMTP') . '</option>';
		$html .= '</select><div class="form-text">' . Text::_('COM_PUNGAMAIL_OUTGOING_TRANSPORT_DESC') . '</div></div>';
		$html .= '</div>';
		$html .= '<div id="pungamail-custom-smtp" class="row g-3 mt-0"' . $customHidden . '>';
		$html .= '<div class="col-md-8"><label class="form-label" for="pungamail-smtp-host">' . Text::_('COM_PUNGAMAIL_SERVER') . '</label><input class="form-control" id="pungamail-smtp-host" name="smtp[smtp_host]" value="' . $escape((string) $settings->smtp_host) . '"></div>';
		$html .= '<div class="col-md-4"><label class="form-label" for="pungamail-smtp-port">' . Text::_('COM_PUNGAMAIL_PORT') . '</label><input class="form-control" id="pungamail-smtp-port" type="number" min="1" max="65535" name="smtp[smtp_port]" value="' . (int) $settings->smtp_port . '"></div>';
		$html .= '<div class="col-md-6"><label class="form-label" for="pungamail-smtp-security">' . Text::_('COM_PUNGAMAIL_SECURITY') . '</label><select class="form-select" id="pungamail-smtp-security" name="smtp[smtp_security]">' . $securityOptions . '</select></div>';
		$html .= '<div class="col-md-6 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="smtp[smtp_auth]" value="0"><input class="form-check-input" id="pungamail-smtp-auth" type="checkbox" name="smtp[smtp_auth]" value="1"' . $authChecked . '><label class="form-check-label" for="pungamail-smtp-auth">' . Text::_('COM_PUNGAMAIL_SMTP_AUTHENTICATION') . '</label></div></div>';
		$html .= '<div class="col-md-6"><label class="form-label" for="pungamail-smtp-user">' . Text::_('JGLOBAL_USERNAME') . '</label><input autocomplete="username" class="form-control" id="pungamail-smtp-user" name="smtp[smtp_username]" value="' . $escape((string) $settings->smtp_username) . '"></div>';
		$html .= '<div class="col-md-6"><label class="form-label" for="pungamail-smtp-password">' . Text::_('JGLOBAL_PASSWORD') . '</label><input autocomplete="new-password" class="form-control" id="pungamail-smtp-password" type="password" name="smtp[password]" value="" placeholder="' . $escape($passwordPlaceholder) . '"></div>';
		$html .= '</div>';
		$html .= '<div class="row g-3 mt-0"><div class="col-md-8"><label class="form-label" for="pungamail-smtp-test-email">' . Text::_('COM_PUNGAMAIL_RECIPIENT_EMAIL') . '</label><input class="form-control" id="pungamail-smtp-test-email" type="email" name="smtp[test_email]" value="' . $escape($testEmail) . '"><div class="form-text">' . Text::_('COM_PUNGAMAIL_SMTP_TEST_HELP') . '</div></div></div>';
		$html .= '<input type="hidden" name="pungamail_return" value="options">';
		$html .= '<div class="d-flex flex-wrap gap-2 mt-3"><button class="btn btn-primary" type="submit" formaction="' . $escape($saveUrl) . '" onclick="' . $escape($saveTask) . '">' . Text::_('COM_PUNGAMAIL_SAVE_OUTGOING_SETTINGS') . '</button>';
		$html .= '<button class="btn btn-outline-secondary" type="submit" formaction="' . $escape($testUrl) . '" onclick="' . $escape($testTask) . '">' . Text::_('COM_PUNGAMAIL_SEND_TEST_MAIL') . '</button></div>';
		$html .= '<script>document.addEventListener("DOMContentLoaded",function(){const mode=document.getElementById("pungamail-smtp-mode");const custom=document.getElementById("pungamail-custom-smtp");if(!mode||!custom){return;}const update=function(){custom.hidden=mode.value!=="custom";};mode.addEventListener("change",update);update();});</script>';

		return $html;
	}
}
