<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Resolves and applies the layered Punga Mail email design.
 */
final class MailStyleService
{
	/**
	 * Returns the component-level base style.
	 *
	 * @return array<string,string|int> Style values.
	 */
	public function defaults(): array
	{
		$params = ComponentHelper::getParams('com_pungamail');
		$headingColor = $this->color((string) $params->get('design_heading_color', '#111111'), '#111111');
		$mailHeadingColor = $this->optionalColor((string) $params->get('design_mail_heading_color', ''), $headingColor);

		return [
			'content_width' => $this->integer($params->get('design_content_width', 680), 320, 1200, 680),
			'outer_background' => $this->color((string) $params->get('design_outer_background', '#f4f4f4'), '#f4f4f4'),
			'content_background' => $this->color((string) $params->get('design_content_background', '#ffffff'), '#ffffff'),
			'text_color' => $this->color((string) $params->get('design_text_color', '#222222'), '#222222'),
			'heading_color' => $headingColor,
			'heading_background' => $this->optionalColor((string) $params->get('design_heading_background', '')),
			'mail_heading_color' => $mailHeadingColor,
			'link_color' => $this->color((string) $params->get('design_link_color', '#2457a6'), '#2457a6'),
			'font_family' => $this->fontFamily((string) $params->get('design_font_family', 'Arial, Helvetica, sans-serif')),
			'font_size' => $this->integer($params->get('design_font_size', 16), 10, 28, 16),
			'content_padding' => $this->integer($params->get('design_content_padding', 32), 0, 96, 32),
			'logo_url' => $this->url((string) $params->get('design_logo_url', '')),
			'logo_width' => $this->integer($params->get('design_logo_width', 180), 40, 600, 180),
			'footer_color' => $this->color((string) $params->get('design_footer_color', '#666666'), '#666666'),
			'custom_css' => $this->css((string) $params->get('design_custom_css', '')),
		];
	}

	/**
	 * Resolves component defaults plus optional template/newsletter overrides.
	 *
	 * @param string|null $templateJson    Template override JSON.
	 * @param string|null $newsletterJson  Newsletter override JSON.
	 * @param string|null $templateCss     Template custom CSS.
	 * @param string|null $newsletterCss   Newsletter custom CSS.
	 *
	 * @return array<string,string|int> Effective style.
	 */
	public function resolve(?string $templateJson, ?string $newsletterJson, ?string $templateCss, ?string $newsletterCss): array
	{
		$style = $this->defaults();

		foreach ([$this->decode($templateJson), $this->decode($newsletterJson)] as $overrides)
		{
			foreach ($overrides as $key => $value)
			{
				if ($value === '' || $value === null)
				{
					continue;
				}

				$style[$key] = $this->sanitizeValue($key, $value, $style[$key] ?? '');
			}
		}

		$cssParts = array_filter([
			$this->css((string) ($style['custom_css'] ?? '')),
			$this->css((string) $templateCss),
			$this->css((string) $newsletterCss),
		]);
		$style['custom_css'] = implode("\n\n", $cssParts);

		return $style;
	}

	/**
	 * Encodes editor style overrides, dropping empty inherited values.
	 *
	 * @param array<string,mixed> $input Raw editor values.
	 *
	 * @return string|null JSON or null when every field inherits.
	 */
	public function encodeOverrides(array $input): ?string
	{
		$result = [];

		foreach ($this->overrideKeys() as $key)
		{
			$value = trim((string) ($input[$key] ?? ''));

			if ($value !== '')
			{
				$result[$key] = $value;
			}
		}

		return $result === [] ? null : json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Decodes stored override JSON for editor fields.
	 *
	 * @param string|null $json Stored JSON.
	 *
	 * @return array<string,string> Override values.
	 */
	public function decode(?string $json): array
	{
		if ($json === null || trim($json) === '')
		{
			return [];
		}

		$value = json_decode($json, true);

		return is_array($value) ? array_map(static fn ($item): string => (string) $item, $value) : [];
	}

	/** @return array<int,string> */
	public function overrideKeys(): array
	{
		return [
			'content_width',
			'outer_background',
			'content_background',
			'text_color',
			'heading_color',
			'heading_background',
			'mail_heading_color',
			'link_color',
			'font_family',
			'font_size',
			'content_padding',
			'logo_url',
			'logo_width',
			'footer_color',
		];
	}

	/**
	 * Adds conservative inline styles to the Markdown fragment.
	 *
	 * @param string                  $html  HTML fragment.
	 * @param array<string,string|int> $style Effective style.
	 *
	 * @return string Styled fragment.
	 */
	public function styleFragment(string $html, array $style): string
	{
		$text = htmlspecialchars((string) $style['text_color'], ENT_QUOTES, 'UTF-8');
		$heading = htmlspecialchars((string) $style['heading_color'], ENT_QUOTES, 'UTF-8');
		$link = htmlspecialchars((string) $style['link_color'], ENT_QUOTES, 'UTF-8');
		$fontSize = (int) $style['font_size'];

		$replacements = [
			'<p>' => '<p style="margin:0 0 16px;color:' . $text . ';font-size:' . $fontSize . 'px;line-height:1.55">',
			'<h1>' => '<h1 style="margin:0 0 18px;color:' . $heading . ';line-height:1.2">',
			'<h2>' => '<h2 style="margin:24px 0 14px;color:' . $heading . ';line-height:1.25">',
			'<h3>' => '<h3 style="margin:20px 0 10px;color:' . $heading . ';line-height:1.3">',
			'<h4>' => '<h4 style="margin:18px 0 8px;color:' . $heading . ';line-height:1.3">',
			'<ul>' => '<ul style="margin:0 0 16px;padding-left:24px">',
			'<ol>' => '<ol style="margin:0 0 16px;padding-left:24px">',
			'<li>' => '<li style="margin:0 0 6px">',
			'<hr>' => '<hr style="border:0;border-top:1px solid #d9d9d9;margin:24px 0">',
			'<a href=' => '<a style="color:' . $link . ';text-decoration:underline" href=',
		];

		return str_replace(array_keys($replacements), array_values($replacements), $html);
	}

	/** @param mixed $value @return int */
	private function integer(mixed $value, int $min, int $max, int $fallback): int
	{
		$number = filter_var($value, FILTER_VALIDATE_INT);

		return $number === false ? $fallback : max($min, min($max, (int) $number));
	}

	/** @return string */
	private function color(string $value, string $fallback): string
	{
		$value = trim($value);

		return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $fallback;
	}

	/** @return string */
	private function optionalColor(string $value, string $fallback = ''): string
	{
		$value = trim($value);

		return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $fallback;
	}

	/**
	 * Sanitizes CSS stored inside the generated message style element.
	 *
	 * CSS declarations remain intentionally flexible, but markup delimiters are
	 * removed so custom CSS cannot terminate the style element and inject HTML.
	 *
	 * @param string $value Raw CSS.
	 *
	 * @return string Safe CSS text.
	 */
	private function css(string $value): string
	{
		$value = str_replace(["\0", '<', '>'], '', $value);

		return trim($value);
	}

	/** @return string */
	private function fontFamily(string $value): string
	{
		$value = preg_replace('/[^A-Za-z0-9 ,\-"\']/', '', trim($value)) ?? '';

		return $value !== '' ? $value : 'Arial, Helvetica, sans-serif';
	}

	/** @return string */
	private function url(string $value): string
	{
		$value = trim($value);

		if ($value === '')
		{
			return '';
		}

		if (preg_match('#^https?://#i', $value) === 1)
		{
			return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
		}

		return rtrim(Uri::root(), '/') . '/' . ltrim($value, '/');
	}

	/** @param mixed $value @param mixed $fallback @return string|int */
	private function sanitizeValue(string $key, mixed $value, mixed $fallback): string|int
	{
		return match ($key)
		{
			'content_width' => $this->integer($value, 320, 1200, (int) $fallback),
			'font_size' => $this->integer($value, 10, 28, (int) $fallback),
			'content_padding' => $this->integer($value, 0, 96, (int) $fallback),
			'logo_width' => $this->integer($value, 40, 600, (int) $fallback),
			'outer_background', 'content_background', 'text_color', 'heading_color', 'mail_heading_color', 'link_color', 'footer_color' => $this->color((string) $value, (string) $fallback),
			'heading_background' => strtolower(trim((string) $value)) === 'none' ? '' : $this->optionalColor((string) $value, (string) $fallback),
			'font_family' => $this->fontFamily((string) $value),
			'logo_url' => $this->url((string) $value),
			default => (string) $fallback,
		};
	}
}
