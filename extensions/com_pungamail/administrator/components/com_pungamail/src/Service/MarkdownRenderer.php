<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

/**
 * Small self-contained Markdown renderer for newsletter copy.
 *
 * This is intentionally a conservative Markdown subset. It supports the
 * formatting expected in email copy without executing raw HTML supplied by an
 * editor. Keeping the renderer local also avoids shipping a Composer runtime
 * dependency inside the Joomla package.
 */
final class MarkdownRenderer
{
	/**
	 * Renders safe newsletter Markdown to HTML.
	 *
	 * @param string $markdown Markdown source.
	 *
	 * @return string Sanitized HTML fragment.
	 */
	public function toHtml(string $markdown): string
	{
		$markdown = str_replace(["\r\n", "\r"], "\n", trim($markdown));
		$lines = explode("\n", $markdown);
		$html = [];
		$paragraph = [];
		$listType = null;

		$flushParagraph = function () use (&$paragraph, &$html): void
		{
			if ($paragraph === [])
			{
				return;
			}

			$text = implode(' ', array_map('trim', $paragraph));
			$html[] = '<p>' . $this->inline($text) . '</p>';
			$paragraph = [];
		};

		$closeList = function () use (&$listType, &$html): void
		{
			if ($listType === null)
			{
				return;
			}

			$html[] = '</' . $listType . '>';
			$listType = null;
		};

		foreach ($lines as $line)
		{
			$trimmed = trim($line);

			if ($trimmed === '')
			{
				$flushParagraph();
				$closeList();
				continue;
			}

			if (preg_match('/^(#{1,4})\s+(.+)$/u', $trimmed, $match) === 1)
			{
				$flushParagraph();
				$closeList();
				$level = strlen($match[1]);
				$html[] = '<h' . $level . '>' . $this->inline($match[2]) . '</h' . $level . '>';
				continue;
			}

			if (preg_match('/^[-*]\s+(.+)$/u', $trimmed, $match) === 1)
			{
				$flushParagraph();

				if ($listType !== 'ul')
				{
					$closeList();
					$listType = 'ul';
					$html[] = '<ul>';
				}

				$html[] = '<li>' . $this->inline($match[1]) . '</li>';
				continue;
			}

			if (preg_match('/^\d+\.\s+(.+)$/u', $trimmed, $match) === 1)
			{
				$flushParagraph();

				if ($listType !== 'ol')
				{
					$closeList();
					$listType = 'ol';
					$html[] = '<ol>';
				}

				$html[] = '<li>' . $this->inline($match[1]) . '</li>';
				continue;
			}

			$closeList();
			$paragraph[] = $trimmed;
		}

		$flushParagraph();
		$closeList();

		return implode("\n", $html);
	}

	/**
	 * Converts Markdown to a readable plain-text representation.
	 *
	 * @param string $markdown Markdown source.
	 *
	 * @return string Plain text.
	 */
	public function toText(string $markdown): string
	{
		$text = str_replace(["\r\n", "\r"], "\n", trim($markdown));
		$text = preg_replace('/^#{1,6}\s+/m', '', $text) ?? $text;
		$text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text) ?? $text;
		$text = preg_replace('/__(.+?)__/s', '$1', $text) ?? $text;
		$text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/s', '$1', $text) ?? $text;
		$text = preg_replace('/(?<!_)_([^_]+)_(?!_)/s', '$1', $text) ?? $text;
		$text = preg_replace_callback(
			'/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u',
			static fn (array $match): string => $match[1] . ' (' . $match[2] . ')',
			$text
		) ?? $text;

		return trim($text);
	}

	/**
	 * Applies inline Markdown after escaping all raw HTML.
	 *
	 * @param string $text Source text.
	 *
	 * @return string Safe HTML.
	 */
	private function inline(string $text): string
	{
		$text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text) ?? $text;
		$text = preg_replace('/__(.+?)__/u', '<strong>$1</strong>', $text) ?? $text;
		$text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/u', '<em>$1</em>', $text) ?? $text;
		$text = preg_replace('/(?<!_)_([^_]+)_(?!_)/u', '<em>$1</em>', $text) ?? $text;
		$text = preg_replace_callback(
			'/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u',
			static function (array $match): string
			{
				$url = htmlspecialchars(html_entity_decode($match[2], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');

				return '<a href="' . $url . '">' . $match[1] . '</a>';
			},
			$text
		) ?? $text;

		return $text;
	}
}
