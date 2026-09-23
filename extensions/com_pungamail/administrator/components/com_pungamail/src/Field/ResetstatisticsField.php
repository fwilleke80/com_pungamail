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

/** Renders the destructive Statistics reset action inside Component Options. */
final class ResetstatisticsField extends FormField
{
	/** @var string Joomla form field type. */
	protected $type = 'Resetstatistics';

	/** @return string */
	protected function getInput(): string
	{
		$url = Route::_('index.php?option=com_pungamail&task=statistics.reset');
		$confirm = htmlspecialchars(Text::_('COM_PUNGAMAIL_STATISTICS_RESET_CONFIRM'), ENT_QUOTES, 'UTF-8');
		$label = htmlspecialchars(Text::_('COM_PUNGAMAIL_STATISTICS_RESET_BUTTON'), ENT_QUOTES, 'UTF-8');
		$help = htmlspecialchars(Text::_('COM_PUNGAMAIL_STATISTICS_RESET_DESC'), ENT_QUOTES, 'UTF-8');
		$onclick = "if(!window.confirm('" . addslashes(Text::_('COM_PUNGAMAIL_STATISTICS_RESET_CONFIRM')) . "')){return false;}const task=this.form.querySelector('input[name=task]');if(task){task.value='statistics.reset';}";
		$onclick = htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8');

		return '<div class="alert alert-warning mb-3">' . $help . '</div>'
			. '<button class="btn btn-outline-danger" type="submit" formmethod="post" formaction="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" onclick="' . $onclick . '">' . $label . '</button>';
	}
}
