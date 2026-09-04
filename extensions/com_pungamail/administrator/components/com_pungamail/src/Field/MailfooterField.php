<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Field
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Field;

use Punga\Component\PungaMail\Administrator\Service\MailTextService;

/** Markdown footer field with the localized Website mail text as its default. */
final class MailfooterField extends MarkdownField
{
	/** @var string */
	protected $type = 'Mailfooter';

	/** @return string */
	protected function getInput(): string
	{
		if (trim((string) $this->value) === '')
		{
			$this->value = (new MailTextService())->text('COM_PUNGAMAIL_MAIL_FOOTER_REASON');
		}

		return parent::getInput();
	}
}
