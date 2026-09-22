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
	 * Creates a signed, opaque campaign-visit token without recipient identity.
	 *
	 * @param int                 $newsletterId Newsletter ID.
	 * @param int                 $linkIndex    One-based link index inside the rendered newsletter.
	 * @param array<string,string> $utm          Effective UTM parameters.
	 *
	 * @return string URL-safe signed token.
	 */
	public function createCampaignToken(int $newsletterId, int $linkIndex, array $utm): string
	{
		$payload = [
			'n' => max(0, $newsletterId),
			'l' => max(1, $linkIndex),
			's' => (string) ($utm['utm_source'] ?? ''),
			'm' => (string) ($utm['utm_medium'] ?? ''),
			'c' => (string) ($utm['utm_campaign'] ?? ''),
			'i' => (string) ($utm['utm_id'] ?? ''),
			'o' => (string) ($utm['utm_content'] ?? ''),
		];
		$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		if (!is_string($json))
		{
			throw new \RuntimeException('Unable to encode campaign token payload.');
		}

		$encoded = $this->base64UrlEncode($json);
		$signature = hash_hmac('sha256', 'pungamail-campaign|' . $encoded, $this->secret(), true);

		return $encoded . '.' . $this->base64UrlEncode($signature);
	}

	/**
	 * Validates and decodes a campaign-visit token.
	 *
	 * @return array{newsletter_id:int,link_index:int,utm_source:string,utm_medium:string,utm_campaign:string,utm_id:string,utm_content:string}|null
	 */
	public function validateCampaignToken(string $token): ?array
	{
		$parts = explode('.', trim($token), 2);

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
