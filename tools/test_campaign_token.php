<?php
/** Regression tests for signed Punga Mail campaign tokens. */

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

namespace
{
	require_once __DIR__ . '/../extensions/com_pungamail/administrator/components/com_pungamail/src/Service/TokenService.php';

	use Punga\Component\PungaMail\Administrator\Service\TokenService;

	$service = new TokenService();
	$utm = [
		'utm_source' => 'newsletter',
		'utm_medium' => 'email',
		'utm_campaign' => 'September 2026',
		'utm_id' => '42',
		'utm_content' => 'link-3',
	];
	$token = $service->createCampaignToken(42, 3, $utm);
	$data = $service->validateCampaignToken($token);

	if ($data === null || $data['newsletter_id'] !== 42 || $data['link_index'] !== 3)
	{
		throw new RuntimeException('Valid campaign token did not round-trip newsletter/link identity.');
	}
	foreach ($utm as $key => $value)
	{
		if (($data[$key] ?? null) !== $value)
		{
			throw new RuntimeException('Valid campaign token did not round-trip ' . $key . '.');
		}
	}

	$last = substr($token, -1);
	$tampered = substr($token, 0, -1) . ($last === 'A' ? 'B' : 'A');
	if ($service->validateCampaignToken($tampered) !== null)
	{
		throw new RuntimeException('Tampered campaign token was accepted.');
	}
	if ($service->validateCampaignToken('not-a-valid-token') !== null)
	{
		throw new RuntimeException('Malformed campaign token was accepted.');
	}

	echo "Campaign token tests passed.\n";
}
