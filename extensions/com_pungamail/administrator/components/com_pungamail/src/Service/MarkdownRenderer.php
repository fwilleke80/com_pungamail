<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

/**
 * Small self-contained Markdown renderer for email copy.
 *
 * The renderer deliberately supports a conservative subset suitable for email
 * and escapes all raw HTML. Remote images are emitted only for HTTP(S) or
 * site-relative URLs and receive email-safe responsive inline styling.
 */
final class MarkdownRenderer
{
	/**
	 * Renders safe newsletter Markdown to HTML.
	 *
	 * @param string      $markdown Markdown source.
	 * @param string|null $baseUrl  Site root used to expand relative image URLs.
	 *
	 * @return string Sanitized HTML fragment.
	 */
	public function toHtml(string $markdown, ?string $baseUrl = null): string
	{
		$markdown = str_replace(["\r\n", "\r"], "\n", trim($markdown));
		$lines = explode("\n", $markdown);
		$html = [];
		$paragraph = [];
		$listType = null;

		$flushParagraph = function () use (&$paragraph, &$html, $baseUrl): void
		{
			if ($paragraph === [])
			{
				return;
			}

			$text = implode(' ', array_map('trim', $paragraph));
			$html[] = '<p>' . $this->inline($text, $baseUrl) . '</p>';
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
				$html[] = '<h' . $level . '>' . $this->inline($match[2], $baseUrl) . '</h' . $level . '>';
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

				$html[] = '<li>' . $this->inline($match[1], $baseUrl) . '</li>';
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

				$html[] = '<li>' . $this->inline($match[1], $baseUrl) . '</li>';
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
	 * @param string      $markdown Markdown source.
	 * @param string|null $baseUrl  Site root used to expand relative URLs.
	 *
	 * @return string Plain text.
	 */
	public function toText(string $markdown, ?string $baseUrl = null): string
	{
		$text = str_replace(["\r\n", "\r"], "\n", trim($markdown));
		$text = preg_replace('/^#{1,6}\s+/m', '', $text) ?? $text;
		$text = preg_replace_callback(
			'/!\[([^\]]*)\]\(([^\s)]+)\)/u',
			function (array $match) use ($baseUrl): string
			{
				$url = $this->resolveUrl($match[2], $baseUrl);
				$alt = trim($match[1]);

				if ($url === null)
				{
					return $alt;
				}

				return ($alt !== '' ? $alt . ' — ' : '') . $url;
			},
			$text
		) ?? $text;
		$text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text) ?? $text;
		$text = preg_replace('/__(.+?)__/s', '$1', $text) ?? $text;
		$text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/s', '$1', $text) ?? $text;
		$text = preg_replace('/(?<!_)_([^_]+)_(?!_)/s', '$1', $text) ?? $text;
		$text = preg_replace_callback(
			'/\[([^\]]+)\]\(([^\s)]+)\)/u',
			function (array $match) use ($baseUrl): string
			{
				$url = $this->resolveUrl($match[2], $baseUrl);

				return $url === null ? $match[1] : $match[1] . ' (' . $url . ')';
			},
			$text
		) ?? $text;

		return trim($text);
	}

	/**
	 * Applies inline Markdown after escaping all raw HTML.
	 *
	 * @param string      $text    Source text.
	 * @param string|null $baseUrl Optional site base URL.
	 *
	 * @return string Safe HTML.
	 */
	private function inline(string $text, ?string $baseUrl): string
	{
		$text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$text = preg_replace_callback(
			'/!\[([^\]]*)\]\(([^\s)]+)\)/u',
			function (array $match) use ($baseUrl): string
			{
				$url = $this->resolveUrl(html_entity_decode($match[2], ENT_QUOTES, 'UTF-8'), $baseUrl);

				if ($url === null)
				{
					return $match[1];
				}

				$src = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
				$alt = $match[1];

				return '<img src="' . $src . '" alt="' . $alt . '" style="display:block;max-width:100%;height:auto;border:0;">';
			},
			$text
		) ?? $text;
		$text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text) ?? $text;
		$text = preg_replace('/__(.+?)__/u', '<strong>$1</strong>', $text) ?? $text;
		$text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/u', '<em>$1</em>', $text) ?? $text;
		$text = preg_replace('/(?<!_)_([^_]+)_(?!_)/u', '<em>$1</em>', $text) ?? $text;
		$text = preg_replace_callback(
			'/\[([^\]]+)\]\(([^\s)]+)\)/u',
			function (array $match) use ($baseUrl): string
			{
				$url = $this->resolveUrl(html_entity_decode($match[2], ENT_QUOTES, 'UTF-8'), $baseUrl);

				if ($url === null)
				{
					return $match[1];
				}

				return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . $match[1] . '</a>';
			},
			$text
		) ?? $text;

		return $text;
	}

	/**
	 * Accepts only HTTP(S) URLs and expands root-relative/site-relative URLs.
	 *
	 * @param string      $url     Markdown URL.
	 * @param string|null $baseUrl Site root.
	 *
	 * @return string|null Safe absolute URL or null for unsupported schemes.
	 */
	private function resolveUrl(string $url, ?string $baseUrl): ?string
	{
		$url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

		if (preg_match('#^https?://#i', $url) === 1)
		{
			return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
		}

		if ($baseUrl === null || $baseUrl === '' || preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) === 1)
		{
			return null;
		}

		$baseUrl = rtrim($baseUrl, '/') . '/';

		if (str_starts_with($url, '/'))
		{
			$parts = parse_url($baseUrl);

			if (!is_array($parts) || !isset($parts['scheme'], $parts['host']))
			{
				return null;
			}

			$port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

			return $parts['scheme'] . '://' . $parts['host'] . $port . $url;
		}

		if (str_starts_with($url, './'))
		{
			$url = substr($url, 2);
		}

		return $baseUrl . ltrim($url, '/');
	}
}
