<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Field
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Field;

/** Markdown layout editor for items inserted at {new_content}. */
final class NewcontenttemplateField extends MarkdownField
{
	/** @var string Joomla form field type. */
	protected $type = 'Newcontenttemplate';

	/** @return string */
	protected function getInput(): string
	{
		if (!isset($this->element['placeholders']))
		{
			$this->element['placeholders'] = '{title}|{title_link}|{publish_date}|{excerpt}|{read_more}|{url}|{content_type}';
		}

		if (!isset($this->element['helpkeys']))
		{
			$this->element['helpkeys'] = 'COM_PUNGAMAIL_NEW_CONTENT_ITEM_TEMPLATE_PLACEHOLDER_HELP';
		}

		return parent::getInput();
	}
}
