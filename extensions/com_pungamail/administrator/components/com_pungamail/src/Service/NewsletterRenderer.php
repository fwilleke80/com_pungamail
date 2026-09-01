<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Renders newsletter snapshots from Markdown and selected Joomla articles.
 */
final class NewsletterRenderer
{
	public const UNSUBSCRIBE_PLACEHOLDER = '{{PUNGAMAIL_UNSUBSCRIBE_URL}}';
	public const NEW_CONTENT_PLACEHOLDER = '{new_content}';
	public const SITE_NAME_PLACEHOLDER = '{site_name}';
	private const NEW_CONTENT_MARKER = 'PUNGAMAIL_NEW_CONTENT_9F15DDF4';

	/**
	 * @param MarkdownRenderer      $markdown    Markdown renderer.
	 * @param NewsletterRepository $newsletters Newsletter repository.
	 */
	public function __construct(
		private readonly MarkdownRenderer $markdown,
		private readonly NewsletterRepository $newsletters
	)
	{
	}

	/**
	 * Renders one newsletter and item snapshots.
	 *
	 * `{new_content}` is intentionally recognized only on a line by itself.
	 * This keeps the generated block structurally valid and leaves all headings
	 * and surrounding prose under editor control.
	 *
	 * @param object            $newsletter Newsletter row.
	 * @param array<int,object> $items      Selected content rows.
	 *
	 * @return array{subject:string,html:string,text:string,items:array<int,array<string,mixed>>}
	 */
	public function render(object $newsletter, array $items): array
	{
		$siteNameRaw = (string) Factory::getApplication()->get('sitename');
		$siteNameHtml = htmlspecialchars($siteNameRaw, ENT_QUOTES, 'UTF-8');
		$itemHtml = '';
		$itemTextParts = [];
		$snapshots = [];

		foreach ($items as $item)
		{
			$title = trim((string) ($item->title_override ?? '')) ?: (string) ($item->article_title ?? '');
			$rawExcerpt = trim((string) ($item->excerpt_override ?? '')) ?: (string) ($item->article_introtext ?? '');
			$excerpt = $this->plainExcerpt($rawExcerpt);
			$url = $this->newsletters->articleUrl((int) $item->content_id, (int) ($item->catid ?? 0));
			$snapshots[] = [
				'content_id' => (int) $item->content_id,
				'title' => $title,
				'excerpt' => $excerpt,
				'url' => $url,
			];

			$itemHtml .= '<section style="margin:0 0 28px">';
			$itemHtml .= '<h3 style="margin:0 0 8px"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a></h3>';

			if ($excerpt !== '')
			{
				$itemHtml .= '<p style="margin:0 0 8px">' . nl2br(htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8')) . '</p>';
			}

			$itemHtml .= '<p style="margin:0"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(Text::_('COM_PUNGAMAIL_MAIL_READ_ARTICLE'), ENT_QUOTES, 'UTF-8') . ' &rarr;</a></p>';
			$itemHtml .= '</section>';

			$itemText = $title . "\n";

			if ($excerpt !== '')
			{
				$itemText .= $excerpt . "\n";
			}

			$itemText .= $url;
			$itemTextParts[] = $itemText;
		}

		$bodyMarkdown = str_replace(self::SITE_NAME_PLACEHOLDER, $siteNameRaw, (string) $newsletter->body_markdown);
		$bodyMarkdown = preg_replace(
			'/^\s*\{new_content\}\s*$/mi',
			self::NEW_CONTENT_MARKER,
			$bodyMarkdown
		) ?? $bodyMarkdown;
		$baseUrl = Uri::root();
		$bodyHtml = $this->markdown->toHtml($bodyMarkdown, $baseUrl);
		$bodyText = $this->markdown->toText($bodyMarkdown, $baseUrl);
		$bodyHtml = str_replace('<p>' . self::NEW_CONTENT_MARKER . '</p>', $itemHtml, $bodyHtml);
		$bodyHtml = str_replace(self::NEW_CONTENT_MARKER, $itemHtml, $bodyHtml);
		$bodyText = str_replace(self::NEW_CONTENT_MARKER, implode("\n\n", $itemTextParts), $bodyText);

		$html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>';
		$html .= '<body style="margin:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#222">';
		$html .= '<div style="max-width:680px;margin:0 auto;padding:32px 20px;background:#fff">';
		$html .= '<header style="margin-bottom:28px"><strong>' . $siteNameHtml . '</strong></header>';
		$html .= '<main style="line-height:1.55">' . $bodyHtml . '</main>';
		$html .= '<footer style="margin-top:36px;padding-top:18px;border-top:1px solid #ddd;font-size:12px;color:#666">';
		$html .= '<p>' . htmlspecialchars(Text::_('COM_PUNGAMAIL_MAIL_FOOTER_REASON'), ENT_QUOTES, 'UTF-8') . '</p>';
		$html .= '<p><a href="' . self::UNSUBSCRIBE_PLACEHOLDER . '">' . htmlspecialchars(Text::_('COM_PUNGAMAIL_MAIL_UNSUBSCRIBE'), ENT_QUOTES, 'UTF-8') . '</a></p>';
		$html .= '</footer></div></body></html>';

		$text = trim($bodyText);
		$text .= "\n\n---\n" . Text::_('COM_PUNGAMAIL_MAIL_UNSUBSCRIBE') . ': ' . self::UNSUBSCRIBE_PLACEHOLDER . "\n";

		return [
			'subject' => (string) $newsletter->subject,
			'html' => $html,
			'text' => $text,
			'items' => $snapshots,
		];
	}

	/**
	 * Produces a short plain-text excerpt from Joomla article HTML.
	 *
	 * @param string $value Article intro text or override.
	 *
	 * @return string Excerpt.
	 */
	private function plainExcerpt(string $value): string
	{
		$value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

		if (mb_strlen($value, 'UTF-8') > 320)
		{
			$value = rtrim(mb_substr($value, 0, 317, 'UTF-8')) . '…';
		}

		return $value;
	}
}
