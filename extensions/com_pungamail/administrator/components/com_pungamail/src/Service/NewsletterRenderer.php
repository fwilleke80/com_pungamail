<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * Renders newsletter/template Markdown into immutable email-ready output.
 */
final class NewsletterRenderer
{
	public const UNSUBSCRIBE_PLACEHOLDER = '{{PUNGAMAIL_UNSUBSCRIBE_URL}}';
	public const NEW_CONTENT_PLACEHOLDER = '{new_content}';
	public const RECIPIENT_PLACEHOLDER = '{recipient}';

	/**
	 * @param MarkdownRenderer   $markdown     Markdown renderer.
	 * @param ContentTypeService $contentTypes Registered content-type service.
	 * @param MailStyleService   $styles       Mail-style resolver.
	 * @param TemplateRepository $templates    Template repository.
	 * @param MailTextService     $mailText     Frontend mail-language resolver.
	 */
	public function __construct(
		private readonly MarkdownRenderer $markdown,
		private readonly ContentTypeService $contentTypes,
		private readonly MailStyleService $styles,
		private readonly TemplateRepository $templates,
		private readonly MailTextService $mailText
	)
	{
	}

	/**
	 * Renders one newsletter and its selected content items.
	 *
	 * @param object            $newsletter Newsletter row.
	 * @param array<int,object> $items      Punga Mail selection rows.
	 *
	 * @return array{subject:string,html:string,text:string,items:array<int,array<string,mixed>>}
	 */
	public function render(object $newsletter, array $items): array
	{
		$template = isset($newsletter->template_id) && (int) $newsletter->template_id > 0
			? $this->templates->find((int) $newsletter->template_id)
			: null;
		$style = $this->styles->resolve(
			$template?->style_overrides ?? null,
			$newsletter->style_overrides ?? null,
			$template?->custom_css ?? null,
			$newsletter->custom_css ?? null
		);
		$siteNameRaw = (string) Factory::getApplication()->get('sitename');
		$itemHtml = '';
		$itemTextParts = [];
		$snapshots = [];

		foreach ($items as $selection)
		{
			$current = $this->contentTypes->findItem((string) $selection->source_key, (string) $selection->source_item_id);
			$title = trim((string) ($selection->title_override ?? ''));
			$excerpt = trim((string) ($selection->excerpt_override ?? ''));

			if ($title === '')
			{
				$title = (string) ($current?->title ?? $selection->snapshot_title ?? '');
			}

			if ($excerpt === '')
			{
				$excerpt = $this->plainExcerpt((string) ($current?->body ?? $selection->snapshot_excerpt ?? ''));
			}
			else
			{
				$excerpt = $this->plainExcerpt($excerpt);
			}

			$url = (string) ($current?->url ?? $selection->snapshot_url ?? '');
			$snapshots[] = [
				'source_key' => (string) $selection->source_key,
				'source_item_id' => (string) $selection->source_item_id,
				'title' => $title,
				'excerpt' => $excerpt,
				'url' => $url,
			];

			$titleHtml = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
			$urlHtml = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
			$linkColor = htmlspecialchars((string) $style['link_color'], ENT_QUOTES, 'UTF-8');
			$headingColor = htmlspecialchars((string) $style['heading_color'], ENT_QUOTES, 'UTF-8');
			$textColor = htmlspecialchars((string) $style['text_color'], ENT_QUOTES, 'UTF-8');
			$itemHtml .= '<section style="margin:0 0 28px">';
			$itemHtml .= '<h3 style="margin:0 0 8px;color:' . $headingColor . '">';
			$itemHtml .= $url !== ''
				? '<a style="color:' . $linkColor . ';text-decoration:underline" href="' . $urlHtml . '">' . $titleHtml . '</a>'
				: $titleHtml;
			$itemHtml .= '</h3>';

			if ($excerpt !== '')
			{
				$itemHtml .= '<p style="margin:0 0 8px;color:' . $textColor . ';line-height:1.55">' . nl2br(htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8')) . '</p>';
			}

			if ($url !== '')
			{
				$itemHtml .= '<p style="margin:0"><a style="color:' . $linkColor . ';text-decoration:underline" href="' . $urlHtml . '">' . htmlspecialchars($this->mailText->text('COM_PUNGAMAIL_MAIL_READ_MORE'), ENT_QUOTES, 'UTF-8') . ' &rarr;</a></p>';
			}

			$itemHtml .= '</section>';
			$itemText = $title;

			if ($excerpt !== '')
			{
				$itemText .= "\n" . $excerpt;
			}

			if ($url !== '')
			{
				$itemText .= "\n" . $url;
			}

			$itemTextParts[] = $itemText;
		}

		$bodyMarkdown = (string) $newsletter->body_markdown;
		$bodyParts = preg_split('/^\s*\{new_content\}\s*$/mi', $bodyMarkdown);

		if (!is_array($bodyParts))
		{
			$bodyParts = [$bodyMarkdown];
		}

		$htmlParts = array_map(
			fn (string $part): string => $this->markdown->toHtml($part, Uri::root()),
			$bodyParts
		);
		$textParts = array_map(
			fn (string $part): string => $this->markdown->toText($part, Uri::root()),
			$bodyParts
		);
		$bodyHtml = implode($itemHtml, $htmlParts);
		$itemTextBlock = implode("\n\n", $itemTextParts);
		$textSeparator = $itemTextBlock !== '' ? "\n\n" . $itemTextBlock . "\n\n" : "\n\n";
		$bodyText = implode($textSeparator, $textParts);
		$bodyHtml = $this->styles->styleFragment($bodyHtml, $style);
		$footerMarkdown = $this->footerMarkdown();
		$footerHtml = $this->styleFooterFragment($this->markdown->toHtml($footerMarkdown, Uri::root()), $style);
		$footerText = trim($this->markdown->toText($footerMarkdown, Uri::root()));
		$html = $this->wrapHtml($bodyHtml, $footerHtml, $siteNameRaw, $style);
		$text = trim($bodyText) . "\n\n---\n";

		if ($footerText !== '')
		{
			$text .= $footerText . "\n\n";
		}

		$text .= $this->mailText->text('COM_PUNGAMAIL_MAIL_UNSUBSCRIBE') . ': ' . self::UNSUBSCRIBE_PLACEHOLDER . "\n";

		return [
			'subject' => (string) $newsletter->subject,
			'html' => $html,
			'text' => $text,
			'items' => $snapshots,
		];
	}

	/**
	 * Renders a template preview using the same style pipeline and no content items.
	 *
	 * @param object $template Template row.
	 *
	 * @return array{subject:string,html:string,text:string,items:array<int,array<string,mixed>>}
	 */
	/**
	 * Resolves recipient-specific placeholders after the newsletter snapshot has
	 * been rendered. This keeps one immutable newsletter snapshot while allowing
	 * each queued recipient to receive personalized text safely.
	 *
	 * The recipient value is HTML-escaped for HTML output and used verbatim for
	 * the plain-text alternative. Subject personalization is supported as well,
	 * even though the editor primarily documents the placeholder for the body.
	 *
	 * @param string $subject       Rendered/frozen subject.
	 * @param string $html          Rendered/frozen HTML body.
	 * @param string $text          Rendered/frozen plain-text body.
	 * @param string $recipientName Display name, or email fallback.
	 *
	 * @return array{subject:string,html:string,text:string}
	 */
	public function personalize(string $subject, string $html, string $text, string $recipientName): array
	{
		$recipientName = trim($recipientName);
		$htmlRecipient = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');

		return [
			'subject' => str_replace(self::RECIPIENT_PLACEHOLDER, $recipientName, $subject),
			'html' => str_replace(self::RECIPIENT_PLACEHOLDER, $htmlRecipient, $html),
			'text' => str_replace(self::RECIPIENT_PLACEHOLDER, $recipientName, $text),
		];
	}

	public function renderTemplate(object $template): array
	{
		$newsletter = (object) [
			'subject' => (string) $template->subject,
			'body_markdown' => (string) $template->body_markdown,
			'template_id' => (int) $template->id,
			'style_overrides' => null,
			'custom_css' => null,
		];

		return $this->render($newsletter, []);
	}

	/** @param array<string,string|int> $style @return string */
	private function wrapHtml(string $bodyHtml, string $footerHtml, string $siteName, array $style): string
	{
		$width = (int) $style['content_width'];
		$padding = (int) $style['content_padding'];
		$fontSize = (int) $style['font_size'];
		$outer = htmlspecialchars((string) $style['outer_background'], ENT_QUOTES, 'UTF-8');
		$content = htmlspecialchars((string) $style['content_background'], ENT_QUOTES, 'UTF-8');
		$text = htmlspecialchars((string) $style['text_color'], ENT_QUOTES, 'UTF-8');
		$link = htmlspecialchars((string) $style['link_color'], ENT_QUOTES, 'UTF-8');
		$footer = htmlspecialchars((string) $style['footer_color'], ENT_QUOTES, 'UTF-8');
		$font = htmlspecialchars((string) $style['font_family'], ENT_QUOTES, 'UTF-8');
		$customCss = trim((string) ($style['custom_css'] ?? ''));
		$headCss = $customCss !== '' ? '<style>' . $customCss . '</style>' : '';
		$header = '<strong>' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</strong>';
		$logoUrl = (string) ($style['logo_url'] ?? '');

		if ($logoUrl !== '')
		{
			$header = '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '" width="' . (int) $style['logo_width'] . '" style="display:block;max-width:100%;height:auto;border:0">';
		}

		$html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' . $headCss . '</head>';
		$html .= '<body style="margin:0;background:' . $outer . ';font-family:' . $font . ';font-size:' . $fontSize . 'px;color:' . $text . '">';
		$html .= '<div style="max-width:' . $width . 'px;margin:0 auto;padding:' . $padding . 'px;background:' . $content . '">';
		$html .= '<header style="margin-bottom:28px">' . $header . '</header>';
		$html .= '<main style="line-height:1.55">' . $bodyHtml . '</main>';
		$html .= '<footer style="margin-top:36px;padding-top:18px;border-top:1px solid #dddddd;font-size:12px;color:' . $footer . '">';
		$html .= $footerHtml;
		$html .= '<p style="margin:10px 0 0"><a style="color:' . $link . ';text-decoration:underline" href="' . self::UNSUBSCRIBE_PLACEHOLDER . '">' . htmlspecialchars($this->mailText->text('COM_PUNGAMAIL_MAIL_UNSUBSCRIBE'), ENT_QUOTES, 'UTF-8') . '</a></p>';
		$html .= '</footer></div></body></html>';

		return $html;
	}

	/**
	 * Returns the configured Markdown footer reason or its localized Website default.
	 *
	 * @return string Markdown footer text.
	 */
	private function footerMarkdown(): string
	{
		$params = ComponentHelper::getParams('com_pungamail');
		$value = trim((string) $params->get('mail_footer_reason', ''));

		return $value !== '' ? $value : $this->mailText->text('COM_PUNGAMAIL_MAIL_FOOTER_REASON');
	}

	/**
	 * Applies small, conservative footer-specific inline styles to Markdown.
	 *
	 * @param string                   $html  Footer HTML fragment.
	 * @param array<string,string|int> $style Effective mail style.
	 *
	 * @return string Styled footer fragment.
	 */
	private function styleFooterFragment(string $html, array $style): string
	{
		$link = htmlspecialchars((string) $style['link_color'], ENT_QUOTES, 'UTF-8');
		$replacements = [
			'<p>' => '<p style="margin:0 0 10px">',
			'<ul>' => '<ul style="margin:0 0 10px;padding-left:20px">',
			'<ol>' => '<ol style="margin:0 0 10px;padding-left:20px">',
			'<li>' => '<li style="margin:0 0 4px">',
			'<a href=' => '<a style="color:' . $link . ';text-decoration:underline" href=',
		];

		return str_replace(array_keys($replacements), array_values($replacements), $html);
	}

	/** @return string */
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
