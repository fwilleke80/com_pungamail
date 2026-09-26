<?php
/** Regression tests for Punga Mail campaign URL tagging. */

namespace Joomla\CMS
{
	final class Factory
	{
		public static function getConfig(): object
		{
			return new class
			{
				public function get(string $name): string
				{
					return $name === 'secret' ? 'punga-mail-test-secret' : '';
				}
			};
		}
	}
}

namespace Joomla\CMS\Component
{
	final class ComponentHelper
	{
		public static array $values = [
			'campaign_tracking_scope' => 'all',
			'campaign_utm_source' => 'newsletter',
			'campaign_utm_medium' => 'email',
			'campaign_utm_campaign' => '{newsletter_title}',
			'campaign_utm_id' => '{newsletter_id}',
			'campaign_utm_content' => 'link-{link_index}',
		];

		public static function getParams(string $component): object
		{
			return new class
			{
				public function get(string $name, mixed $default = null): mixed
				{
					return ComponentHelper::$values[$name] ?? $default;
				}
			};
		}
	}
}

namespace Joomla\CMS\Uri
{
	final class Uri
	{
		private string $scheme = '';
		private string $host = '';
		private string $path = '';
		private string $fragment = '';
		private array $query = [];

		public function __construct(string $url = '')
		{
			$parts = parse_url($url) ?: [];
			$this->scheme = (string) ($parts['scheme'] ?? '');
			$this->host = (string) ($parts['host'] ?? '');
			$this->path = (string) ($parts['path'] ?? '');
			$this->fragment = (string) ($parts['fragment'] ?? '');
			parse_str((string) ($parts['query'] ?? ''), $this->query);
		}

		public static function root(): string
		{
			return 'https://example.test/';
		}

		public function getHost(): string { return $this->host; }
		public function getPath(): string { return $this->path; }
		public function getFragment(): string { return $this->fragment; }
		public function getQuery(bool $toArray = false): array|string
		{
			if ($toArray)
			{
				return $this->query;
			}

			$parts = [];
			foreach ($this->query as $name => $value)
			{
				$parts[] = (string) $name . '=' . (string) $value;
			}

			return implode('&', $parts);
		}
		public function setVar(string $name, string $value): void { $this->query[$name] = $value; }

		public function toString(array $parts = ['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment']): string
		{
			$base = '';
			if (in_array('scheme', $parts, true) && $this->scheme !== '')
			{
				$base .= $this->scheme . '://';
			}
			if (in_array('host', $parts, true))
			{
				$base .= $this->host;
			}
			if (in_array('path', $parts, true))
			{
				$base .= $this->path;
			}
			if (in_array('query', $parts, true) && $this->query !== [])
			{
				$base .= '?' . $this->getQuery(false);
			}
			if (in_array('fragment', $parts, true) && $this->fragment !== '')
			{
				$base .= '#' . $this->fragment;
			}

			return $base;
		}
	}
}

namespace Punga\Component\PungaMail\Administrator\Service
{
	final class NewsletterRenderer
	{
		public const UNSUBSCRIBE_PLACEHOLDER = '__PM_UNSUBSCRIBE__';
		public const BROWSER_PLACEHOLDER = '__PM_BROWSER__';
	}
}

namespace
{
	require_once __DIR__ . '/../extensions/com_pungamail/administrator/components/com_pungamail/src/Service/TokenService.php';
	require_once __DIR__ . '/../extensions/com_pungamail/administrator/components/com_pungamail/src/Service/CampaignTrackingService.php';

	use Joomla\CMS\Component\ComponentHelper;
	use Punga\Component\PungaMail\Administrator\Service\CampaignTrackingService;
	use Punga\Component\PungaMail\Administrator\Service\TokenService;

	$service = new CampaignTrackingService(new TokenService());
	$newsletter = (object) [
		'id' => 42,
		'title' => 'September Update',
		'campaign_scope' => 'inherit',
		'utm_source' => '', 'utm_medium' => '', 'utm_campaign' => '', 'utm_id' => '', 'utm_content' => '',
	];
	$html = '<a href="/inside?foo=1#part">Inside</a> <a href="https://outside.example/path">Outside</a>';
	$text = "Inside https://example.test/inside\nOutside https://outside.example/path";
	$result = $service->apply($html, $text, $newsletter);

	if (!str_contains($result['html'], 'utm_source=newsletter') || !str_contains($result['html'], 'pm_track='))
	{
		throw new RuntimeException('Internal HTML link did not receive UTM parameters and signed token.');
	}
	if (!str_contains($result['html'], 'outside.example/path?utm_source=newsletter'))
	{
		throw new RuntimeException('External HTML link did not receive UTM parameters in all-links mode.');
	}
	$externalChunk = substr($result['html'], strpos($result['html'], 'outside.example'));
	if (str_contains($externalChunk, 'pm_track='))
	{
		throw new RuntimeException('External HTML link incorrectly received pm_track.');
	}
	if (!str_contains($result['html'], '#part'))
	{
		throw new RuntimeException('Tracked internal URL lost its fragment.');
	}

	$specialTitle = 'Test-Digest — 25. September 2026 — copy & + Übergröß';
	$specialNewsletter = clone $newsletter;
	$specialNewsletter->title = $specialTitle;
	$specialResult = $service->apply('<a href="https://example.test/article?existing=one#section">Special</a>', '', $specialNewsletter);
	if (!preg_match('/href="([^"]+)"/', $specialResult['html'], $specialMatch))
	{
		throw new RuntimeException('Could not extract tracked special-character URL.');
	}
	$specialUrl = html_entity_decode((string) $specialMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
	if (filter_var($specialUrl, FILTER_VALIDATE_URL) === false)
	{
		throw new RuntimeException('Tracked URL with Unicode campaign title is not a syntactically valid URL: ' . $specialUrl);
	}
	if (!str_contains($specialUrl, 'utm_campaign=Test-Digest%20%E2%80%94%2025.%20September%202026%20%E2%80%94%20copy%20%26%20%2B%20%C3%9Cbergr%C3%B6%C3%9F'))
	{
		throw new RuntimeException('Campaign UTM value was not RFC 3986 encoded.');
	}
	if (str_contains($specialUrl, ' ') || str_contains($specialUrl, '—'))
	{
		throw new RuntimeException('Tracked URL still contains unescaped spaces or Unicode punctuation.');
	}
	if (!str_ends_with($specialUrl, '#section'))
	{
		throw new RuntimeException('RFC 3986 query serialization lost the URL fragment.');
	}
	parse_str((string) parse_url($specialUrl, PHP_URL_QUERY), $specialVars);
	if (($specialVars['existing'] ?? '') !== 'one' || ($specialVars['utm_campaign'] ?? '') !== $specialTitle)
	{
		throw new RuntimeException('RFC 3986 query serialization did not preserve existing or decoded campaign values.');
	}
	$specialUtm = [];
	foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_content'] as $name)
	{
		$specialUtm[$name] = (string) ($specialVars[$name] ?? '');
	}
	$specialData = (new TokenService())->validateCampaignToken((string) ($specialVars['pm_track'] ?? ''), $specialUtm);
	if ($specialData === null || (int) $specialData['newsletter_id'] !== 42 || (int) $specialData['link_index'] !== 1)
	{
		throw new RuntimeException('Encoded UTM values no longer validate against pm_track.');
	}

	$preview = $service->apply($html, $text, $newsletter, false);
	if (!str_contains($preview['html'], 'utm_source=newsletter'))
	{
		throw new RuntimeException('Preview/test tracking must retain visible UTM parameters.');
	}
	if (str_contains($preview['html'], 'pm_track='))
	{
		throw new RuntimeException('Preview/test tracking must not emit trusted pm_track tokens.');
	}


	ComponentHelper::$values['campaign_tracking_scope'] = 'all';
	$unsubscribe = $service->actionUrl('https://example.test/newsletter/unsubscribe?id=2&mid=42', $newsletter, 'unsubscribe', true);
	if (!str_contains($unsubscribe, 'utm_source=newsletter') || !str_contains($unsubscribe, 'utm_content=unsubscribe') || !str_contains($unsubscribe, 'pm_track='))
	{
		throw new RuntimeException('Tracked unsubscribe action did not receive campaign parameters and pm_track.');
	}
	parse_str((string) parse_url($unsubscribe, PHP_URL_QUERY), $unsubscribeVars);
	$unsubscribeUtm = [];
	foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_content'] as $name)
	{
		$unsubscribeUtm[$name] = (string) ($unsubscribeVars[$name] ?? '');
	}
	$unsubscribeData = (new TokenService())->validateCampaignToken((string) ($unsubscribeVars['pm_track'] ?? ''), $unsubscribeUtm);
	if ($unsubscribeData === null || (int) $unsubscribeData['newsletter_id'] !== 42 || (int) $unsubscribeData['link_index'] !== 0)
	{
		throw new RuntimeException('Tracked unsubscribe action did not use the reserved authenticated action token.');
	}

	ComponentHelper::$values['campaign_tracking_scope'] = 'internal';
	$internalOnly = $service->apply($html, $text, $newsletter);
	if (!str_contains($internalOnly['html'], 'outside.example/path">Outside'))
	{
		throw new RuntimeException('External link changed in internal-only mode.');
	}

	ComponentHelper::$values['campaign_tracking_scope'] = 'disabled';
	$disabled = $service->apply($html, $text, $newsletter);
	if ($disabled['html'] !== $html || $disabled['text'] !== $text)
	{
		throw new RuntimeException('Disabled campaign tracking modified message links.');
	}

	echo "Campaign tracking URL tests passed.\n";
}
