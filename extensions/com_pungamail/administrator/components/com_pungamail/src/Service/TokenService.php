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
