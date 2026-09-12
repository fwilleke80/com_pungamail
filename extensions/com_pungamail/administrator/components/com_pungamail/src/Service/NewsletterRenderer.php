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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Renders newsletter/template Markdown into immutable email-ready output.
 */
final class NewsletterRenderer
{
	public const UNSUBSCRIBE_PLACEHOLDER = '{{PUNGAMAIL_UNSUBSCRIBE_URL}}';
	public const NEW_CONTENT_PLACEHOLDER = '{new_content}';
	public const RECIPIENT_PLACEHOLDER = '{recipient}';
	public const BROWSER_PLACEHOLDER = '{{PUNGAMAIL_BROWSER_URL}}';

	/**
	 * @param MarkdownRenderer   $markdown     Markdown renderer.
	 * @param ContentTypeService $contentTypes Registered content-type service.
	 * @param MailStyleService   $styles       Mail-style resolver.
	 * @param TemplateRepository $templates    Template repository.
	 * @param MailTextService          $mailText       Frontend mail-language resolver.
	 * @param ContentLayoutRepository $contentLayouts Central selected-content layouts.
	 */
	public function __construct(
		private readonly MarkdownRenderer $markdown,
		private readonly ContentTypeService $contentTypes,
		private readonly MailStyleService $styles,
		private readonly TemplateRepository $templates,
		private readonly MailTextService $mailText,
		private readonly MailConfigurationService $mailConfiguration,
		private readonly ContentLayoutRepository $contentLayouts,
		private readonly UserFieldService $userFields
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
		$heading = $this->mailConfiguration->heading($template, $newsletter);
		$browserView = $this->mailConfiguration->browserView($template, $newsletter);
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
			$published = (string) ($current?->published ?? '');
			$contentType = (string) ($current?->source_label ?? $selection->source_key);
			$publishDate = $this->formatPublishDate($published);
			$titleMarkdown = $this->escapeMarkdown($title);
			$excerptMarkdown = $this->escapeMarkdown($excerpt);
			$contentTypeMarkdown = $this->escapeMarkdown($contentType);
			$linkUrl = $this->markdownUrl($url);
			$titleLink = $url !== '' ? '[' . $titleMarkdown . '](' . $linkUrl . ')' : $titleMarkdown;
			$readMore = $url !== ''
				? '[' . $this->escapeMarkdown($this->mailText->text('COM_PUNGAMAIL_MAIL_READ_MORE') . ' →') . '](' . $linkUrl . ')'
				: '';
			$itemTemplate = $this->contentLayouts->layoutFor((string) $selection->source_key);
			$itemMarkdown = $this->renderContentItemTemplate(
				$itemTemplate,
				[
					'title' => $titleMarkdown,
					'title_link' => $titleLink,
					'publish_date' => $this->escapeMarkdown($publishDate),
					'excerpt' => $excerptMarkdown,
					'read_more' => $readMore,
					'url' => $url,
					'content_type' => $contentTypeMarkdown,
				],
				is_array($current?->raw_fields ?? null) ? $current->raw_fields : []
			);
			$itemHtml .= $this->markdown->toHtml($itemMarkdown, Uri::root());
			$itemTextParts[] = trim($this->markdown->toText($itemMarkdown, Uri::root()));
			$snapshots[] = [
				'source_key' => (string) $selection->source_key,
				'source_item_id' => (string) $selection->source_item_id,
				'title' => $title,
				'excerpt' => $excerpt,
				'url' => $url,
			];
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
		$footerMarkdown = $this->footerMarkdown($style);
		$footerHtml = $this->styleFooterFragment($this->markdown->toHtml($footerMarkdown, Uri::root()), $style);
		$footerText = trim($this->markdown->toText($footerMarkdown, Uri::root()));
		$html = $this->wrapHtml($bodyHtml, $footerHtml, $heading, $browserView, $style);
		$text = $browserView
			? $this->mailText->text('COM_PUNGAMAIL_MAIL_VIEW_BROWSER') . ': ' . self::BROWSER_PLACEHOLDER . "\n\n" . trim($bodyText) . "\n\n---\n"
			: trim($bodyText) . "\n\n---\n";

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
	 * @param string   $recipientName Display name, or email fallback.
	 * @param int|null $userId        Linked Joomla user ID, when available.
	 *
	 * @return array{subject:string,html:string,text:string}
	 */
	public function personalize(string $subject, string $html, string $text, string $recipientName, ?int $userId = null): array
	{
		$recipientName = trim($recipientName);
		$htmlRecipient = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
		$userFields = $this->userFields->valuesForUser($userId);

		return [
			'subject' => $this->replaceUserFieldPlaceholders(str_replace(self::RECIPIENT_PLACEHOLDER, $recipientName, $subject), $userFields, false),
			'html' => $this->replaceUserFieldPlaceholders(str_replace(self::RECIPIENT_PLACEHOLDER, $htmlRecipient, $html), $userFields, true),
			'text' => $this->replaceUserFieldPlaceholders(str_replace(self::RECIPIENT_PLACEHOLDER, $recipientName, $text), $userFields, false),
		];
	}

	/**
	 * Replaces Joomla User Custom Field placeholders in one recipient output.
	 *
	 * Unknown aliases and recipients without a Joomla account intentionally
	 * resolve to an empty string so no implementation token leaks into mail.
	 *
	 * @param string               $content    Subject, HTML, or plain-text content.
	 * @param array<string,string> $userFields Published field values by lower-case alias.
	 * @param bool                 $escapeHtml Escape replacement values for HTML output.
	 *
	 * @return string Personalized content.
	 */
	private function replaceUserFieldPlaceholders(string $content, array $userFields, bool $escapeHtml): string
	{
		$result = preg_replace_callback(
			'/\{userfield\|([A-Za-z0-9_-]+)\}/i',
			static function (array $match) use ($userFields, $escapeHtml): string
			{
				$value = (string) ($userFields[strtolower((string) $match[1])] ?? '');

				return $escapeHtml ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
			},
			$content
		);

		return is_string($result) ? $result : $content;
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

	/**
	 * Resolves generic and source-table placeholders in one selected-content layout.
	 * Generic Punga Mail placeholders deliberately take precedence over database
	 * columns with the same name so title/excerpt overrides continue to work.
	 *
	 * @param string                    $layout  Markdown layout.
	 * @param array<string,string>      $generic Normalized Punga Mail values.
	 * @param array<string,mixed>       $raw     Safe source-table values.
	 *
	 * @return string Rendered Markdown.
	 */
	private function renderContentItemTemplate(string $layout, array $generic, array $raw): string
	{
		return preg_replace_callback(
			'/\{([A-Za-z0-9_]+)(?:\|(date|time|datetime))?\}/',
			function (array $match) use ($generic, $raw): string
			{
				$name = (string) $match[1];
				$format = (string) ($match[2] ?? '');

				if (array_key_exists($name, $generic))
				{
					return $generic[$name];
				}

				if (!array_key_exists($name, $raw))
				{
					return $match[0];
				}

				$value = $raw[$name];

				if ($value === null)
				{
					return '';
				}

				$text = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				$text = $text === false ? '' : $text;

				if ($format !== '')
				{
					$text = $this->formatDatabaseDate($text, $format);
				}

				return $this->escapeMarkdown($text);
			},
			$layout
		) ?? $layout;
	}

	/** @return string */
	private function formatDatabaseDate(string $value, string $format): string
	{
		if (trim($value) === '')
		{
			return '';
		}

		try
		{
			$date = Factory::getDate($value, 'UTC');
			$date->setTimezone(new \DateTimeZone((string) Factory::getApplication()->get('offset', 'UTC')));
			$pattern = match ($format)
			{
				'date' => Text::_('DATE_FORMAT_LC3'),
				'time' => 'H:i',
				default => Text::_('DATE_FORMAT_LC5'),
			};

			return $date->format($pattern, true);
		}
		catch (\Throwable)
		{
			return $value;
		}
	}

	/** @return string */
	private function formatPublishDate(string $value): string
	{
		if (trim($value) === '')
		{
			return '';
		}

		try
		{
			$date = Factory::getDate($value, 'UTC');
			$date->setTimezone(new \DateTimeZone((string) Factory::getApplication()->get('offset', 'UTC')));

			return $date->format(Text::_('DATE_FORMAT_LC3'), true);
		}
		catch (\Throwable)
		{
			return '';
		}
	}

	/** @return string */
	private function escapeMarkdown(string $value): string
	{
		return preg_replace_callback(
			'/([\\`*_{}\[\]()#+.!|>~-])/',
			static fn (array $match): string => '\\' . $match[1],
			$value
		) ?? $value;
	}
	/** @return string */
	private function markdownUrl(string $value): string
	{
		return str_replace([' ', '(', ')'], ['%20', '%28', '%29'], $value);
	}

	/** @param array<string,string|int> $style @return string */
	private function wrapHtml(string $bodyHtml, string $footerHtml, string $heading, bool $browserView, array $style): string
	{
		$width = (int) $style['content_width'];
		$padding = (int) $style['content_padding'];
		$fontSize = (int) $style['font_size'];
		$outer = htmlspecialchars((string) $style['outer_background'], ENT_QUOTES, 'UTF-8');
		$content = htmlspecialchars((string) $style['content_background'], ENT_QUOTES, 'UTF-8');
		$text = htmlspecialchars((string) $style['text_color'], ENT_QUOTES, 'UTF-8');
		$link = htmlspecialchars((string) $style['link_color'], ENT_QUOTES, 'UTF-8');
		$font = htmlspecialchars((string) $style['font_family'], ENT_QUOTES, 'UTF-8');
		$customCss = trim((string) ($style['custom_css'] ?? ''));
		$headCss = $customCss !== '' ? '<style>' . $customCss . '</style>' : '';
		$siteName = (string) Factory::getApplication()->get('sitename');
		$logoUrl = (string) ($style['logo_url'] ?? '');
		$logoWidth = (int) ($style['logo_width'] ?? 180);
		$logoPosition = (string) ($style['logo_position'] ?? 'above');
		$browserBackground = htmlspecialchars((string) ($style['browser_background'] ?? $content), ENT_QUOTES, 'UTF-8');
		$browserLinkColor = htmlspecialchars((string) ($style['browser_link_color'] ?? $link), ENT_QUOTES, 'UTF-8');
		$browserAlignment = $this->alignment((string) ($style['browser_alignment'] ?? 'center'));
		$browserPadding = (int) ($style['browser_padding'] ?? 6);
		$headingBackground = htmlspecialchars((string) ($style['heading_background'] ?? ''), ENT_QUOTES, 'UTF-8');
		$headerBackgroundImage = htmlspecialchars((string) ($style['header_background_image'] ?? ''), ENT_QUOTES, 'UTF-8');
		$headerBackgroundImageBehavior = (string) ($style['header_background_image_behavior'] ?? 'cover');
		$mailHeadingColor = htmlspecialchars((string) ($style['mail_heading_color'] ?? $style['heading_color']), ENT_QUOTES, 'UTF-8');
		$headerAlignment = $this->alignment((string) ($style['header_alignment'] ?? 'left'));
		$headerPadding = (int) ($style['header_padding'] ?? 20);
		$headerGap = (int) ($style['header_gap'] ?? 12);
		$footerBackground = htmlspecialchars((string) ($style['footer_background'] ?? $content), ENT_QUOTES, 'UTF-8');
		$footerBackgroundImage = htmlspecialchars((string) ($style['footer_background_image'] ?? ''), ENT_QUOTES, 'UTF-8');
		$footerColor = htmlspecialchars((string) ($style['footer_color'] ?? '#666666'), ENT_QUOTES, 'UTF-8');
		$footerLinkColor = htmlspecialchars((string) ($style['footer_link_color'] ?? $link), ENT_QUOTES, 'UTF-8');
		$footerAlignment = $this->alignment((string) ($style['footer_alignment'] ?? 'left'));
		$footerPadding = (int) ($style['footer_padding'] ?? 24);
		$footerDivider = (int) ($style['footer_divider'] ?? 1) === 1;
		$footerDividerColor = htmlspecialchars((string) ($style['footer_divider_color'] ?? '#dddddd'), ENT_QUOTES, 'UTF-8');
		$headerPresent = $heading !== '' || $logoUrl !== '';

		$html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' . $headCss . '</head>';
		$html .= '<body style="margin:0;padding:0;background:' . $outer . ';font-family:' . $font . ';font-size:' . $fontSize . 'px;color:' . $text . ';-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%">';
		$html .= '<table class="pm-mail-outer" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;background:' . $outer . '"><tr><td align="center" style="padding:0">';
		$html .= '<table class="pm-mail-container" role="presentation" width="' . $width . '" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:' . $width . 'px;border-collapse:collapse;background:' . $content . '"><tr><td style="padding:0">';

		if ($browserView)
		{
			$html .= '<table class="pm-browser-link" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="' . $browserBackground . '" style="width:100%;border-collapse:collapse;background:' . $browserBackground . ';background-color:' . $browserBackground . '"><tr><td align="' . $browserAlignment . '" style="padding:' . $browserPadding . 'px ' . $padding . 'px;text-align:' . $browserAlignment . '">';
			$html .= '<a class="pm-browser-link-anchor" style="color:' . $browserLinkColor . ';text-decoration:underline;font-size:12px;line-height:1.3" href="' . self::BROWSER_PLACEHOLDER . '">' . htmlspecialchars($this->mailText->text('COM_PUNGAMAIL_MAIL_VIEW_BROWSER'), ENT_QUOTES, 'UTF-8') . '</a>';
			$html .= '</td></tr></table>';
		}

		if ($headerPresent)
		{
			$backgroundStyle = $headingBackground !== '' ? 'background-color:' . $headingBackground . ';' : '';
			$backgroundAttribute = $headingBackground !== '' ? ' bgcolor="' . $headingBackground . '"' : '';
			$backgroundImageStyle = $headerBackgroundImage !== ''
				? 'background-image:url(&quot;' . $headerBackgroundImage . '&quot;);' . $this->backgroundImageStyle($headerBackgroundImageBehavior)
				: '';
			$backgroundImageAttribute = $headerBackgroundImage !== '' ? ' background="' . $headerBackgroundImage . '"' : '';
			$imageMargin = match ($headerAlignment)
			{
				'center' => '0 auto',
				'right' => '0 0 0 auto',
				default => '0 auto 0 0',
			};
			$logoHtml = $logoUrl !== ''
				? '<img class="pm-mail-logo-image" src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '" width="' . $logoWidth . '" style="display:block;max-width:100%;height:auto;border:0;margin:' . $imageMargin . '">'
				: '';
			$headingHtml = $heading !== ''
				? '<h1 class="pm-mail-heading-title" style="margin:0;color:' . $mailHeadingColor . ';line-height:1.2;text-align:' . $headerAlignment . '">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h1>'
				: '';
			$gapHtml = $logoHtml !== '' && $headingHtml !== '' ? '<div class="pm-mail-heading-gap" style="height:' . $headerGap . 'px;line-height:' . $headerGap . 'px;font-size:1px">&nbsp;</div>' : '';
			$headerContent = $logoPosition === 'below'
				? $headingHtml . $gapHtml . $logoHtml
				: $logoHtml . $gapHtml . $headingHtml;

			$html .= '<table class="pm-mail-heading" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"' . $backgroundAttribute . $backgroundImageAttribute . ' style="width:100%;border-collapse:collapse;' . $backgroundStyle . $backgroundImageStyle . '"><tr><td class="pm-mail-heading-cell" width="100%" align="' . $headerAlignment . '"' . $backgroundAttribute . $backgroundImageAttribute . ' style="width:100%;padding:' . $headerPadding . 'px;text-align:' . $headerAlignment . ';' . $backgroundStyle . $backgroundImageStyle . '">';
			$html .= '<div class="pm-mail-logo">' . $headerContent . '</div>';
			$html .= '</td></tr></table>';
		}

		$html .= '<div class="pm-mail-content" style="padding:' . $padding . 'px;background:' . $content . ';background-color:' . $content . '">';
		$html .= '<main class="pm-mail-body" style="line-height:1.55">' . $bodyHtml . '</main>';
		$html .= '</div>';

		$dividerStyle = $footerDivider ? 'border-top:1px solid ' . $footerDividerColor . ';' : '';
		$footerBackgroundImageStyle = $footerBackgroundImage !== ''
			? 'background-image:url(&quot;' . $footerBackgroundImage . '&quot;);background-repeat:no-repeat;background-position:center center;background-size:cover;'
			: '';
		$footerBackgroundImageAttribute = $footerBackgroundImage !== '' ? ' background="' . $footerBackgroundImage . '"' : '';
		$html .= '<table class="pm-mail-footer" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="' . $footerBackground . '"' . $footerBackgroundImageAttribute . ' style="width:100%;border-collapse:collapse;background-color:' . $footerBackground . ';' . $footerBackgroundImageStyle . '"><tr><td align="' . $footerAlignment . '"' . $footerBackgroundImageAttribute . ' style="padding:' . $footerPadding . 'px;text-align:' . $footerAlignment . ';font-size:12px;color:' . $footerColor . ';background-color:' . $footerBackground . ';' . $footerBackgroundImageStyle . $dividerStyle . '">';
		$html .= $footerHtml;
		$html .= '<p style="margin:10px 0 0"><a style="color:' . $footerLinkColor . ';text-decoration:underline" href="' . self::UNSUBSCRIBE_PLACEHOLDER . '">' . htmlspecialchars($this->mailText->text('COM_PUNGAMAIL_MAIL_UNSUBSCRIBE'), ENT_QUOTES, 'UTF-8') . '</a></p>';
		$html .= '</td></tr></table>';
		$html .= '</td></tr></table></td></tr></table></body></html>';

		return $html;
	}


	/**
	 * Returns conservative CSS for a header background-image display mode.
	 *
	 * @param string $behavior Selected display behavior.
	 *
	 * @return string CSS declarations.
	 */
	private function backgroundImageStyle(string $behavior): string
	{
		return match ($behavior)
		{
			'contain' => 'background-repeat:no-repeat;background-position:center center;background-size:contain;',
			'tile' => 'background-repeat:repeat;background-position:left top;background-size:auto;',
			'tile_x' => 'background-repeat:repeat-x;background-position:left top;background-size:auto;',
			'tile_y' => 'background-repeat:repeat-y;background-position:left top;background-size:auto;',
			'original' => 'background-repeat:no-repeat;background-position:left top;background-size:auto;',
			default => 'background-repeat:no-repeat;background-position:center center;background-size:cover;',
		};
	}

	/** @return string */
	private function alignment(string $value): string
	{
		return in_array($value, ['left', 'center', 'right'], true) ? $value : 'left';
	}

	/**
	 * Returns the configured Markdown footer reason or its localized Website default.
	 *
	 * @return string Markdown footer text.
	 */
	private function footerMarkdown(array $style): string
	{
		if ((string) ($style['footer_reason_mode'] ?? 'custom') === 'none')
		{
			return '';
		}

		$value = trim((string) ($style['footer_reason'] ?? ''));

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
		$link = htmlspecialchars((string) ($style['footer_link_color'] ?? $style['link_color']), ENT_QUOTES, 'UTF-8');
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
		$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$value = $this->stripContentPluginTokens($value);
		$value = strip_tags($value);
		$value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

		if (mb_strlen($value, 'UTF-8') > 320)
		{
			$value = rtrim(mb_substr($value, 0, 317, 'UTF-8')) . '…';
		}

		return $value;
	}

	/**
	 * Removes unresolved Joomla-style content-plugin command markers from excerpt source.
	 *
	 * Content plugins are deliberately not executed for newsletter excerpts. Removing only
	 * command-shaped brace tokens keeps readable text between paired markers while avoiding
	 * raw commands such as {snippet alias="example"} in outgoing mail.
	 *
	 * @param string $value Article text before HTML stripping and excerpt truncation.
	 *
	 * @return string Text with plugin-like command markers removed.
	 */
	private function stripContentPluginTokens(string $value): string
	{
		return preg_replace(
			'/\{\/?[A-Za-z][A-Za-z0-9_.-]*(?:(?:\s+|=)[^{}\r\n]*)?\}/u',
			'',
			$value
		) ?? $value;
	}
}
