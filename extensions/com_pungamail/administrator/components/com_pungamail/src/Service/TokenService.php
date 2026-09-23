<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Factory;

/**
 * Creates and validates Punga Mail tokens.
 */
final class TokenService
{
	/**
	 * Creates a random confirmation token and its storage hash.
	 *
	 * @return array{token:string,hash:string}
	 */
	public function createConfirmationToken(): array
	{
		$token = $this->base64UrlEncode(random_bytes(32));

		return [
			'token' => $token,
			'hash' => hash('sha256', $token),
		];
	}

	/**
	 * Hashes a supplied confirmation token for database lookup.
	 *
	 * @param string $token Raw token from the confirmation URL.
	 *
	 * @return string SHA-256 hash.
	 */
	public function hashConfirmationToken(string $token): string
	{
		return hash('sha256', $token);
	}

	/**
	 * Creates a stable unsubscribe signature for a subscriber.
	 *
	 * @param int $subscriberId Subscriber primary key.
	 *
	 * @return string URL-safe HMAC token.
	 */
	public function createUnsubscribeToken(int $subscriberId): string
	{
		$payload = 'pungamail-unsubscribe|' . $subscriberId;
		$secret = (string) Factory::getApplication()->get('secret');
		$signature = hash_hmac('sha256', $payload, $secret, true);

		return $this->base64UrlEncode($signature);
	}

	/**
	 * Validates an unsubscribe token using a timing-safe comparison.
	 *
	 * @param int    $subscriberId Subscriber primary key.
	 * @param string $token        Token supplied by the client.
	 *
	 * @return bool True when the signature is valid.
	 */
	public function validateUnsubscribeToken(int $subscriberId, string $token): bool
	{
		$expected = $this->createUnsubscribeToken($subscriberId);

		return hash_equals($expected, $token);
	}

	/**
	 * Creates a compact authenticated campaign-visit token without recipient identity.
	 *
	 * The UTM values remain visible in the URL and are covered by the HMAC rather than
	 * duplicated inside the token. The 128-bit truncated HMAC remains cryptographically
	 * strong for URL authentication while keeping newsletter URLs reasonably short.
	 *
	 * @param int                  $newsletterId Newsletter ID.
	 * @param int                  $linkIndex    One-based link index inside the rendered newsletter.
	 * @param array<string,string> $utm          Effective UTM parameters.
	 *
	 * @return string URL-safe authenticated token.
	 */
	public function createCampaignToken(int $newsletterId, int $linkIndex, array $utm): string
	{
		$newsletterId = max(0, $newsletterId);
		$linkIndex = max(0, $linkIndex);
		$newsletterPart = base_convert((string) $newsletterId, 10, 36);
		$linkPart = base_convert((string) $linkIndex, 10, 36);
		$payload = $this->campaignSignaturePayload($newsletterId, $linkIndex, $utm);
		$signature = substr(hash_hmac('sha256', $payload, $this->secret(), true), 0, 16);

		return '2.' . $newsletterPart . '.' . $linkPart . '.' . $this->base64UrlEncode($signature);
	}

	/**
	 * Validates and decodes a campaign-visit token.
	 *
	 * Compact v2 tokens authenticate the visible UTM values supplied by the caller.
	 * Legacy v1 tokens (0.6.27-0.6.29) remain accepted so links in already-sent
	 * newsletters continue to produce trusted campaign visits.
	 *
	 * @param string               $token Token supplied by the client.
	 * @param array<string,string> $utm   Visible UTM values from the request/URL.
	 *
	 * @return array{newsletter_id:int,link_index:int,utm_source:string,utm_medium:string,utm_campaign:string,utm_id:string,utm_content:string}|null
	 */
	public function validateCampaignToken(string $token, array $utm = []): ?array
	{
		$token = trim($token);

		if (str_starts_with($token, '2.'))
		{
			return $this->validateCompactCampaignToken($token, $utm);
		}

		return $this->validateLegacyCampaignToken($token);
	}

	/**
	 * @param array<string,string> $utm
	 *
	 * @return array{newsletter_id:int,link_index:int,utm_source:string,utm_medium:string,utm_campaign:string,utm_id:string,utm_content:string}|null
	 */
	private function validateCompactCampaignToken(string $token, array $utm): ?array
	{
		$parts = explode('.', $token);
		if (count($parts) !== 4 || $parts[0] !== '2' || $parts[1] === '' || $parts[2] === '' || $parts[3] === '')
		{
			return null;
		}
		if (preg_match('/^[0-9a-z]+$/', $parts[1]) !== 1 || preg_match('/^[0-9a-z]+$/', $parts[2]) !== 1)
		{
			return null;
		}

		$newsletterId = (int) base_convert($parts[1], 36, 10);
		$linkIndex = (int) base_convert($parts[2], 36, 10);
		if ($newsletterId <= 0 || $linkIndex < 0)
		{
			return null;
		}

		$normalizedUtm = $this->normalizeCampaignUtm($utm);
		$payload = $this->campaignSignaturePayload($newsletterId, $linkIndex, $normalizedUtm);
		$expected = $this->base64UrlEncode(substr(hash_hmac('sha256', $payload, $this->secret(), true), 0, 16));
		if (!hash_equals($expected, $parts[3]))
		{
			return null;
		}

		return ['newsletter_id' => $newsletterId, 'link_index' => $linkIndex] + $normalizedUtm;
	}

	/**
	 * Validates the long campaign-token format emitted by Punga Mail 0.6.27-0.6.29.
	 *
	 * @return array{newsletter_id:int,link_index:int,utm_source:string,utm_medium:string,utm_campaign:string,utm_id:string,utm_content:string}|null
	 */
	private function validateLegacyCampaignToken(string $token): ?array
	{
		$parts = explode('.', $token, 2);

		if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '')
		{
			return null;
		}

		$expected = $this->base64UrlEncode(hash_hmac('sha256', 'pungamail-campaign|' . $parts[0], $this->secret(), true));
		if (!hash_equals($expected, $parts[1]))
		{
			return null;
		}

		$json = $this->base64UrlDecode($parts[0]);
		$data = is_string($json) ? json_decode($json, true) : null;
		if (!is_array($data) || (int) ($data['n'] ?? 0) <= 0 || (int) ($data['l'] ?? 0) <= 0)
		{
			return null;
		}

		return [
			'newsletter_id' => (int) $data['n'],
			'link_index' => (int) $data['l'],
			'utm_source' => (string) ($data['s'] ?? ''),
			'utm_medium' => (string) ($data['m'] ?? ''),
			'utm_campaign' => (string) ($data['c'] ?? ''),
			'utm_id' => (string) ($data['i'] ?? ''),
			'utm_content' => (string) ($data['o'] ?? ''),
		];
	}

	/**
	 * @param array<string,string> $utm
	 */
	private function campaignSignaturePayload(int $newsletterId, int $linkIndex, array $utm): string
	{
		$values = $this->normalizeCampaignUtm($utm);
		$json = json_encode([
			$newsletterId,
			$linkIndex,
			$values['utm_source'],
			$values['utm_medium'],
			$values['utm_campaign'],
			$values['utm_id'],
			$values['utm_content'],
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		if (!is_string($json))
		{
			throw new \RuntimeException('Unable to encode campaign signature payload.');
		}

		return 'pungamail-campaign-v2|' . $json;
	}

	/**
	 * @param array<string,string> $utm
	 *
	 * @return array{utm_source:string,utm_medium:string,utm_campaign:string,utm_id:string,utm_content:string}
	 */
	private function normalizeCampaignUtm(array $utm): array
	{
		return [
			'utm_source' => (string) ($utm['utm_source'] ?? ''),
			'utm_medium' => (string) ($utm['utm_medium'] ?? ''),
			'utm_campaign' => (string) ($utm['utm_campaign'] ?? ''),
			'utm_id' => (string) ($utm['utm_id'] ?? ''),
			'utm_content' => (string) ($utm['utm_content'] ?? ''),
		];
	}

	/** @return string */
	private function secret(): string
	{
		return (string) Factory::getConfig()->get('secret');
	}

	/** @return string|null */
	private function base64UrlDecode(string $value): ?string
	{
		$padding = strlen($value) % 4;
		if ($padding > 0)
		{
			$value .= str_repeat('=', 4 - $padding);
		}
		$decoded = base64_decode(strtr($value, '-_', '+/'), true);

		return is_string($decoded) ? $decoded : null;
	}

	/**
	 * Encodes binary data using URL-safe base64 without padding.
	 *
	 * @param string $value Binary value.
	 *
	 * @return string Encoded value.
	 */
	private function base64UrlEncode(string $value): string
	{
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}
}
