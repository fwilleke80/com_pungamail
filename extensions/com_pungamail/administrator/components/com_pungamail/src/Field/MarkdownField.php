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
use Punga\Component\PungaMail\Administrator\Helper\MarkdownEditorHelper;

/** Joomla form field exposing the common Punga Mail Markdown editor. */
class MarkdownField extends TextareaField
{
	/** @var string Joomla form field type. */
	protected $type = 'Markdown';

	/** @return string */
	protected function getInput(): string
	{
		$placeholders = $this->tokens((string) ($this->element['placeholders'] ?? ''));
		$helpKeys = $this->tokens((string) ($this->element['helpkeys'] ?? ''));
		$help = [];

		foreach ($helpKeys as $key)
		{
			$help[] = Text::_($key);
		}

		return MarkdownEditorHelper::render(
			$this->name,
			$this->id,
			(string) $this->value,
			max(4, (int) ($this->element['rows'] ?? 12)),
			$placeholders,
			$help
		);
	}

	/** @return array<string> */
	private function tokens(string $value): array
	{
		return array_values(array_filter(array_map('trim', explode('|', $value)), static fn (string $item): bool => $item !== ''));
	}
}
