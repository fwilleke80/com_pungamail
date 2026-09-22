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
	$data = $service->validateCampaignToken($token, $utm);

	if ($data === null || $data['newsletter_id'] !== 42 || $data['link_index'] !== 3)
	{
		throw new RuntimeException('Valid compact campaign token did not round-trip newsletter/link identity.');
	}
	foreach ($utm as $key => $value)
	{
		if (($data[$key] ?? null) !== $value)
		{
			throw new RuntimeException('Valid compact campaign token did not authenticate ' . $key . '.');
		}
	}
	if (!str_starts_with($token, '2.') || strlen($token) > 40)
	{
		throw new RuntimeException('Campaign token is not using the compact v2 format: ' . $token);
	}

	$tamperedUtm = $utm;
	$tamperedUtm['utm_campaign'] = 'Changed campaign';
	if ($service->validateCampaignToken($token, $tamperedUtm) !== null)
	{
		throw new RuntimeException('Compact campaign token accepted altered visible UTM values.');
	}

	$last = substr($token, -1);
	$tampered = substr($token, 0, -1) . ($last === 'A' ? 'B' : 'A');
	if ($service->validateCampaignToken($tampered, $utm) !== null)
	{
		throw new RuntimeException('Tampered compact campaign token was accepted.');
	}
	if ($service->validateCampaignToken('not-a-valid-token', $utm) !== null)
	{
		throw new RuntimeException('Malformed campaign token was accepted.');
	}

	// 0.6.27-0.6.29 links used base64url(JSON).base64url(HMAC-SHA256).
	// Keep one deterministic legacy vector here so already-sent newsletters remain valid.
	$legacyPayload = json_encode([
		'n' => 42,
		'l' => 3,
		's' => $utm['utm_source'],
		'm' => $utm['utm_medium'],
		'c' => $utm['utm_campaign'],
		'i' => $utm['utm_id'],
		'o' => $utm['utm_content'],
	], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	$base64url = static fn(string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	$legacyEncoded = $base64url((string) $legacyPayload);
	$legacySignature = $base64url(hash_hmac('sha256', 'pungamail-campaign|' . $legacyEncoded, 'punga-mail-test-secret', true));
	$legacyToken = $legacyEncoded . '.' . $legacySignature;
	$legacyData = $service->validateCampaignToken($legacyToken, $utm);
	if ($legacyData === null || $legacyData['newsletter_id'] !== 42 || $legacyData['link_index'] !== 3)
	{
		throw new RuntimeException('Legacy campaign token compatibility was lost.');
	}

	echo "Campaign token tests passed.\n";
}
