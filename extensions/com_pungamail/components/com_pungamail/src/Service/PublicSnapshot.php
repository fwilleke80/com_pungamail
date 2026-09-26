<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\Service;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRenderer;

/** Prepares immutable newsletter snapshots for anonymous frontend display. */
final class PublicSnapshot
{
	/** @return string */
	public static function browserHtml(string $html): string
	{
		return self::neutralizePersonalization($html);
	}

	/** @return string */
	public static function archiveHtml(string $html): string
	{
		$html = self::neutralizePersonalization($html);
		$result = preg_replace_callback(
			'/\bhref=("|\')(.*?)\1/i',
			static function (array $match): string
			{
				$url = html_entity_decode((string) $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
				$clean = self::removeCampaignParameters($url);
				return 'href=' . $match[1] . htmlspecialchars($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $match[1];
			},
			$html
		);

		return is_string($result) ? $result : $html;
	}

	/** @return string */
	private static function neutralizePersonalization(string $html): string
	{
		$html = str_replace(NewsletterRenderer::RECIPIENT_PLACEHOLDER, Text::_('COM_PUNGAMAIL_BROWSER_GENERIC_RECIPIENT'), $html);
		$html = preg_replace('/\{userfield\|[A-Za-z0-9_-]+\}/i', '', $html) ?? $html;
		$html = str_replace('href="' . NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER . '"', 'aria-disabled="true"', $html);
		$html = str_replace('href="' . NewsletterRenderer::BROWSER_PLACEHOLDER . '"', 'aria-disabled="true"', $html);
		return str_replace([NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, NewsletterRenderer::BROWSER_PLACEHOLDER], '#', $html);
	}

	/** @return string */
	private static function removeCampaignParameters(string $url): string
	{
		$url = trim($url);
		$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

		if ($url === '' || str_starts_with($url, '#') || ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)))
		{
			return $url;
		}

		$uri = new Uri($url);
		foreach (['pm_track', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_content'] as $name)
		{
			$uri->delVar($name);
		}

		return $uri->toString();
	}
}
