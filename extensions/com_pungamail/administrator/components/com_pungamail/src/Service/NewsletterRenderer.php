<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Factory;

/**
 * Renders newsletter snapshots from Markdown and selected Joomla articles.
 */
final class NewsletterRenderer
{
	public const UNSUBSCRIBE_PLACEHOLDER = '{{PUNGAMAIL_UNSUBSCRIBE_URL}}';

	/**
	 * @param MarkdownRenderer     $markdown    Markdown renderer.
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
	 * @param object            $newsletter Newsletter row.
	 * @param array<int,object> $items      Selected content rows.
	 *
	 * @return array{subject:string,html:string,text:string,items:array<int,array<string,mixed>>}
	 */
	public function render(object $newsletter, array $items): array
	{
		$siteName = htmlspecialchars((string) Factory::getApplication()->get('sitename'), ENT_QUOTES, 'UTF-8');
		$bodyHtml = $this->markdown->toHtml((string) $newsletter->body_markdown);
		$bodyText = $this->markdown->toText((string) $newsletter->body_markdown);
		$itemHtml = '';
		$itemText = '';
		$snapshots = [];

		if ($items !== [])
		{
			$itemHtml .= '<h2>What&rsquo;s new</h2>';
			$itemText .= "\n\nWHAT'S NEW\n==========\n";
		}

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

			$itemHtml .= '<p style="margin:0"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">Read article &rarr;</a></p>';
			$itemHtml .= '</section>';
			$itemText .= "\n" . $title . "\n";

			if ($excerpt !== '')
			{
				$itemText .= $excerpt . "\n";
			}

			$itemText .= $url . "\n";
		}

		$html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>';
		$html .= '<body style="margin:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#222">';
		$html .= '<div style="max-width:680px;margin:0 auto;padding:32px 20px;background:#fff">';
		$html .= '<header style="margin-bottom:28px"><strong>' . $siteName . '</strong></header>';
		$html .= '<main style="line-height:1.55">' . $bodyHtml . $itemHtml . '</main>';
		$html .= '<footer style="margin-top:36px;padding-top:18px;border-top:1px solid #ddd;font-size:12px;color:#666">';
		$html .= '<p>You are receiving this because you subscribed to this newsletter or your website account is subscribed.</p>';
		$html .= '<p><a href="' . self::UNSUBSCRIBE_PLACEHOLDER . '">Unsubscribe</a></p>';
		$html .= '</footer></div></body></html>';

		$text = trim($bodyText . $itemText);
		$text .= "\n\n---\nUnsubscribe: " . self::UNSUBSCRIBE_PLACEHOLDER . "\n";

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
