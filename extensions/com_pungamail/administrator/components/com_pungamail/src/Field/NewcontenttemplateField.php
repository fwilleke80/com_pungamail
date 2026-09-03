<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Field
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Field;

use Joomla\CMS\Form\Field\TextareaField;
use Joomla\CMS\Language\Text;

/** Markdown layout editor for items inserted at {new_content}. */
final class NewcontenttemplateField extends TextareaField
{
	/** @var string Joomla form field type. */
	protected $type = 'Newcontenttemplate';

	/**
	 * Render a monospaced Markdown editor with always-visible placeholder help.
	 *
	 * @return string Field HTML.
	 */
	protected function getInput(): string
	{
		$classes = preg_split('/\s+/', trim((string) $this->element['class'])) ?: [];

		if (!in_array('font-monospace', $classes, true))
		{
			$classes[] = 'font-monospace';
		}

		$this->element['class'] = trim(implode(' ', array_filter($classes)));

		return parent::getInput()
			. '<div class="form-text">' . Text::_('COM_PUNGAMAIL_NEW_CONTENT_ITEM_TEMPLATE_PLACEHOLDER_HELP') . '</div>';
	}
}
