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
 * Resolves and applies the layered Punga Mail email layout.
 */
final class MailStyleService
{
	/**
	 * Returns the component-level base layout.
	 *
	 * @return array<string,string|int> Layout values.
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
			'header_background_image' => $this->url((string) $params->get('design_header_background_image', '')),
			'mail_heading_color' => $mailHeadingColor,
			'link_color' => $this->color((string) $params->get('design_link_color', '#2457a6'), '#2457a6'),
			'font_family' => $this->fontFamily((string) $params->get('design_font_family', 'Arial, Helvetica, sans-serif')),
			'font_size' => $this->integer($params->get('design_font_size', 16), 10, 28, 16),
			'content_padding' => $this->integer($params->get('design_content_padding', 32), 0, 96, 32),
			'browser_background' => $this->color((string) $params->get('design_browser_background', '#ffffff'), '#ffffff'),
			'browser_link_color' => $this->color((string) $params->get('design_browser_link_color', '#666666'), '#666666'),
			'browser_alignment' => $this->alignment((string) $params->get('design_browser_alignment', 'center'), 'center'),
			'browser_padding' => $this->integer($params->get('design_browser_padding', 6), 0, 48, 6),
			'header_alignment' => $this->alignment((string) $params->get('design_header_alignment', 'left'), 'left'),
			'header_padding' => $this->integer($params->get('design_header_padding', 20), 0, 96, 20),
			'header_gap' => $this->integer($params->get('design_header_gap', 12), 0, 48, 12),
			'logo_url' => $this->url((string) $params->get('design_logo_url', '')),
			'logo_width' => $this->integer($params->get('design_logo_width', 180), 40, 600, 180),
			'logo_position' => $this->logoPosition((string) $params->get('design_logo_position', 'above'), 'above'),
			'footer_background' => $this->color((string) $params->get('design_footer_background', '#ffffff'), '#ffffff'),
			'footer_background_image' => $this->url((string) $params->get('design_footer_background_image', '')),
			'footer_color' => $this->color((string) $params->get('design_footer_color', '#666666'), '#666666'),
			'footer_link_color' => $this->color((string) $params->get('design_footer_link_color', '#2457a6'), '#2457a6'),
			'footer_alignment' => $this->alignment((string) $params->get('design_footer_alignment', 'left'), 'left'),
			'footer_padding' => $this->integer($params->get('design_footer_padding', 24), 0, 96, 24),
			'footer_divider' => (int) $params->get('design_footer_divider', 1) === 1 ? 1 : 0,
			'footer_divider_color' => $this->color((string) $params->get('design_footer_divider_color', '#dddddd'), '#dddddd'),
			'footer_reason_mode' => 'custom',
			'footer_reason' => trim((string) $params->get('mail_footer_reason', '')),
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
	 * @return array<string,string|int> Effective layout.
	 */
	public function resolve(?string $templateJson, ?string $newsletterJson, ?string $templateCss, ?string $newsletterCss): array
	{
		$style = $this->defaults();

		foreach ([$this->decode($templateJson), $this->decode($newsletterJson)] as $overrides)
		{
			$logoMode = (string) ($overrides['logo_mode'] ?? '');

			if ($logoMode === 'none')
			{
				$style['logo_url'] = '';
			}
			elseif ($logoMode === 'custom')
			{
				$style['logo_url'] = $this->url((string) ($overrides['logo_url'] ?? ''));
			}

			foreach (['header_background_image', 'footer_background_image'] as $imageKey)
			{
				$imageMode = (string) ($overrides[$imageKey . '_mode'] ?? '');

				if ($imageMode === 'none')
				{
					$style[$imageKey] = '';
				}
				elseif ($imageMode === 'custom')
				{
					$style[$imageKey] = $this->url((string) ($overrides[$imageKey] ?? ''));
				}
			}

			$footerMode = (string) ($overrides['footer_reason_mode'] ?? 'inherit');

			if ($footerMode === 'none')
			{
				$style['footer_reason_mode'] = 'none';
				$style['footer_reason'] = '';
			}
			elseif ($footerMode === 'custom')
			{
				$style['footer_reason_mode'] = 'custom';
				$style['footer_reason'] = $this->footerReason((string) ($overrides['footer_reason'] ?? ''));
			}

			foreach ($overrides as $key => $value)
			{
				if (in_array($key, ['logo_mode', 'header_background_image_mode', 'footer_background_image_mode', 'footer_reason_mode', 'footer_reason'], true))
				{
					continue;
				}

				if ($key === 'logo_url' && $logoMode !== '')
				{
					continue;
				}

				if (in_array($key, ['header_background_image', 'footer_background_image'], true) && array_key_exists($key . '_mode', $overrides))
				{
					continue;
				}

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
	 * Encodes editor layout overrides, dropping inherited values.
	 *
	 * Empty ordinary fields mean "inherit". Logo and footer-content modes have
	 * explicit inherit/custom/none states because an empty value is meaningful.
	 *
	 * @param array<string,mixed> $input Raw editor values.
	 *
	 * @return string|null JSON or null when every field inherits.
	 */
	public function encodeOverrides(array $input): ?string
	{
		$result = [];
		$logoModeProvided = array_key_exists('logo_mode', $input);
		$logoMode = (string) ($input['logo_mode'] ?? 'inherit');

		if ($logoModeProvided && $logoMode === 'none')
		{
			$result['logo_mode'] = 'none';
		}
		elseif ($logoModeProvided && $logoMode === 'custom')
		{
			$result['logo_mode'] = 'custom';
			$result['logo_url'] = trim((string) ($input['logo_url'] ?? ''));
		}

		foreach (['header_background_image', 'footer_background_image'] as $imageKey)
		{
			$modeKey = $imageKey . '_mode';
			$imageModeProvided = array_key_exists($modeKey, $input);
			$imageMode = (string) ($input[$modeKey] ?? 'inherit');

			if ($imageModeProvided && $imageMode === 'none')
			{
				$result[$modeKey] = 'none';
			}
			elseif ($imageModeProvided && $imageMode === 'custom')
			{
				$result[$modeKey] = 'custom';
				$result[$imageKey] = trim((string) ($input[$imageKey] ?? ''));
			}
		}

		$footerModeProvided = array_key_exists('footer_reason_mode', $input);
		$footerMode = (string) ($input['footer_reason_mode'] ?? 'inherit');

		if ($footerModeProvided && $footerMode === 'none')
		{
			$result['footer_reason_mode'] = 'none';
		}
		elseif ($footerModeProvided && $footerMode === 'custom')
		{
			$result['footer_reason_mode'] = 'custom';
			$result['footer_reason'] = $this->footerReason((string) ($input['footer_reason'] ?? ''));
		}

		foreach ($this->overrideKeys() as $key)
		{
			if ($key === 'logo_url' && $logoModeProvided)
			{
				continue;
			}

			if (in_array($key, ['header_background_image', 'footer_background_image'], true) && array_key_exists($key . '_mode', $input))
			{
				continue;
			}

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
			'browser_background',
			'browser_link_color',
			'browser_alignment',
			'browser_padding',
			'heading_background',
			'header_background_image',
			'mail_heading_color',
			'header_alignment',
			'header_padding',
			'header_gap',
			'logo_url',
			'logo_width',
			'logo_position',
			'content_background',
			'text_color',
			'heading_color',
			'link_color',
			'font_family',
			'font_size',
			'content_padding',
			'footer_background',
			'footer_background_image',
			'footer_color',
			'footer_link_color',
			'footer_alignment',
			'footer_padding',
			'footer_divider',
			'footer_divider_color',
		];
	}

	/**
	 * Adds conservative inline styles to the Markdown fragment.
	 *
	 * @param string                    $html  HTML fragment.
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

	/** @return string */
	private function alignment(string $value, string $fallback): string
	{
		return in_array($value, ['left', 'center', 'right'], true) ? $value : $fallback;
	}

	/** @return string */
	private function logoPosition(string $value, string $fallback): string
	{
		return in_array($value, ['above', 'below'], true) ? $value : $fallback;
	}

	/** @return string */
	private function footerReason(string $value): string
	{
		return trim(str_replace("\0", '', $value));
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

		if (str_contains($value, '#joomlaImage://'))
		{
			$value = strstr($value, '#', true) ?: '';
		}

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
			'browser_padding' => $this->integer($value, 0, 48, (int) $fallback),
			'header_padding' => $this->integer($value, 0, 96, (int) $fallback),
			'header_gap' => $this->integer($value, 0, 48, (int) $fallback),
			'logo_width' => $this->integer($value, 40, 600, (int) $fallback),
			'footer_padding' => $this->integer($value, 0, 96, (int) $fallback),
			'outer_background', 'content_background', 'text_color', 'heading_color', 'mail_heading_color', 'link_color', 'footer_color', 'footer_link_color', 'browser_background', 'browser_link_color', 'footer_background', 'footer_divider_color' => $this->color((string) $value, (string) $fallback),
			'heading_background' => strtolower(trim((string) $value)) === 'none' ? '' : $this->optionalColor((string) $value, (string) $fallback),
			'browser_alignment', 'header_alignment', 'footer_alignment' => $this->alignment((string) $value, (string) $fallback),
			'logo_position' => $this->logoPosition((string) $value, (string) $fallback),
			'footer_divider' => (int) $value === 0 ? 0 : 1,
			'font_family' => $this->fontFamily((string) $value),
			'logo_url', 'header_background_image', 'footer_background_image' => $this->url((string) $value),
			default => (string) $fallback,
		};
	}
}
