<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Field
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Field;

use Joomla\CMS\Form\Field\TextareaField;
use Punga\Component\PungaMail\Administrator\Service\MailTextService;

/**
 * Markdown footer field with the localized Website mail text as its default.
 */
final class MailfooterField extends TextareaField
{
	/** @var string */
	protected $type = 'Mailfooter';

	/**
	 * Renders the textarea, showing the Website-language footer when no custom
	 * component value has been stored yet.
	 *
	 * @return string Field HTML.
	 */
	protected function getInput(): string
	{
		if (trim((string) $this->value) === '')
		{
			$this->value = (new MailTextService())->text('COM_PUNGAMAIL_MAIL_FOOTER_REASON');
		}

		return parent::getInput();
	}
}
