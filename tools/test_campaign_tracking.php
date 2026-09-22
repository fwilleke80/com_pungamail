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
		public function setVar(string $name, string $value): void { $this->query[$name] = $value; }

		public function toString(): string
		{
			$base = ($this->scheme !== '' ? $this->scheme . '://' . $this->host : '') . $this->path;
			if ($this->query !== []) { $base .= '?' . http_build_query($this->query, '', '&', PHP_QUERY_RFC3986); }
			if ($this->fragment !== '') { $base .= '#' . $this->fragment; }
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

	$preview = $service->apply($html, $text, $newsletter, false);
	if (!str_contains($preview['html'], 'utm_source=newsletter'))
	{
		throw new RuntimeException('Preview/test tracking must retain visible UTM parameters.');
	}
	if (str_contains($preview['html'], 'pm_track='))
	{
		throw new RuntimeException('Preview/test tracking must not emit trusted pm_track tokens.');
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
