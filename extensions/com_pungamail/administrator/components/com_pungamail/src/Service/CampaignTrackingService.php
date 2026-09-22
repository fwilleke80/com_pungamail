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

/** Adds configurable UTM campaign tags and trusted internal visit tokens. */
final class CampaignTrackingService
{
	public const SCOPE_INHERIT = 'inherit';
	public const SCOPE_DISABLED = 'disabled';
	public const SCOPE_INTERNAL = 'internal';
	public const SCOPE_ALL = 'all';

	public function __construct(private readonly TokenService $tokens)
	{
	}

	/**
	 * Applies campaign tracking to HTML and plain-text output.
	 *
	 * @return array{html:string,text:string}
	 */
	public function apply(string $html, string $text, object $newsletter, bool $includeTrustedToken = true): array
	{
		$settings = $this->settings($newsletter);

		if ($settings['scope'] === self::SCOPE_DISABLED)
		{
			return ['html' => $html, 'text' => $text];
		}

		$map = [];
		$linkIndex = 0;
		$htmlResult = preg_replace_callback(
			'/\bhref=("|\')(.*?)\1/i',
			function (array $match) use (&$map, &$linkIndex, $newsletter, $settings, $includeTrustedToken): string
			{
				$decoded = html_entity_decode((string) $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
				$tracked = $this->trackedUrl($decoded, $newsletter, $settings, $linkIndex, $map, $includeTrustedToken);
				return 'href=' . $match[1] . htmlspecialchars($tracked, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $match[1];
			},
			$html
		);
		$htmlResult = is_string($htmlResult) ? $htmlResult : $html;

		$textResult = preg_replace_callback(
			'~https?://[^\s<>]+~i',
			function (array $match) use (&$map, &$linkIndex, $newsletter, $settings, $includeTrustedToken): string
			{
				$url = rtrim((string) $match[0], ".,;:!?)\"]'");
				$suffix = substr((string) $match[0], strlen($url));
				return $this->trackedUrl($url, $newsletter, $settings, $linkIndex, $map, $includeTrustedToken) . $suffix;
			},
			$text
		);

		return [
			'html' => $htmlResult,
			'text' => is_string($textResult) ? $textResult : $text,
		];
	}

	/**
	 * Resolves global defaults plus newsletter-level overrides.
	 *
	 * @return array{scope:string,utm_source:string,utm_medium:string,utm_campaign:string,utm_id:string,utm_content:string}
	 */
	public function settings(object $newsletter): array
	{
		$params = ComponentHelper::getParams('com_pungamail');
		$scope = $this->scope((string) ($newsletter->campaign_scope ?? self::SCOPE_INHERIT));

		if ($scope === self::SCOPE_INHERIT)
		{
			$scope = $this->scope((string) $params->get('campaign_tracking_scope', self::SCOPE_DISABLED));
		}

		if ($scope === self::SCOPE_INHERIT)
		{
			$scope = self::SCOPE_DISABLED;
		}

		return [
			'scope' => $scope,
			'utm_source' => $this->override($newsletter, 'utm_source', (string) $params->get('campaign_utm_source', 'pungamail')),
			'utm_medium' => $this->override($newsletter, 'utm_medium', (string) $params->get('campaign_utm_medium', 'email')),
			'utm_campaign' => $this->override($newsletter, 'utm_campaign', (string) $params->get('campaign_utm_campaign', '{newsletter_title}')),
			'utm_id' => $this->override($newsletter, 'utm_id', (string) $params->get('campaign_utm_id', '{newsletter_id}')),
			'utm_content' => $this->override($newsletter, 'utm_content', (string) $params->get('campaign_utm_content', 'link-{link_index}')),
		];
	}

	/** @return string */
	private function trackedUrl(string $url, object $newsletter, array $settings, int &$linkIndex, array &$map, bool $includeTrustedToken): string
	{
		if (isset($map[$url]))
		{
			return $map[$url];
		}

		if (!$this->trackable($url))
		{
			return $url;
		}

		$uri = new Uri($url);
		$host = strtolower((string) $uri->getHost());
		$siteHost = strtolower((string) (new Uri(Uri::root()))->getHost());
		$internal = $host === '' || $host === $siteHost;

		if ($settings['scope'] === self::SCOPE_INTERNAL && !$internal)
		{
			return $url;
		}

		$linkIndex++;
		$context = [
			'{newsletter_id}' => (string) max(0, (int) ($newsletter->id ?? 0)),
			'{newsletter_title}' => trim((string) ($newsletter->title ?? $newsletter->subject ?? '')),
			'{link_index}' => (string) $linkIndex,
			'{link_host}' => $host,
			'{link_path}' => (string) $uri->getPath(),
		];
		$utm = [];

		foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_content'] as $name)
		{
			$value = trim(strtr((string) $settings[$name], $context));
			if ($value !== '')
			{
				$utm[$name] = $value;
				$uri->setVar($name, $value);
			}
		}

		if ($includeTrustedToken && $internal && (int) ($newsletter->id ?? 0) > 0)
		{
			$uri->setVar('pm_track', $this->tokens->createCampaignToken((int) $newsletter->id, $linkIndex, $utm));
		}

		$tracked = $uri->toString();
		$map[$url] = $tracked;

		return $tracked;
	}

	/** @return bool */
	private function trackable(string $url): bool
	{
		$url = trim($url);
		if ($url === '' || str_starts_with($url, '#'))
		{
			return false;
		}
		if (str_contains($url, NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER)
			|| str_contains($url, NewsletterRenderer::BROWSER_PLACEHOLDER))
		{
			return false;
		}
		$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
		return $scheme === '' || in_array($scheme, ['http', 'https'], true);
	}

	/** @return string */
	private function override(object $newsletter, string $property, string $fallback): string
	{
		$value = trim((string) ($newsletter->{$property} ?? ''));
		return $value !== '' ? $value : trim($fallback);
	}

	/** @return string */
	private function scope(string $scope): string
	{
		return in_array($scope, [self::SCOPE_INHERIT, self::SCOPE_DISABLED, self::SCOPE_INTERNAL, self::SCOPE_ALL], true)
			? $scope
			: self::SCOPE_INHERIT;
	}
}
