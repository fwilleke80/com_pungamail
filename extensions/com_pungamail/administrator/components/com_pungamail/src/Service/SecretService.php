<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Factory;

/** Encrypts retrievable component secrets with the Joomla installation secret. */
final class SecretService
{
	/** @return string */
	public function encrypt(string $plainText): string
	{
		if ($plainText === '')
		{
			return '';
		}

		$key = $this->key();

		if (function_exists('sodium_crypto_secretbox'))
		{
			$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$cipher = sodium_crypto_secretbox($plainText, $nonce, $key);

			return 's1:' . base64_encode($nonce . $cipher);
		}

		$nonce = random_bytes(12);
		$tag = '';
		$cipher = openssl_encrypt($plainText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);

		if (!is_string($cipher))
		{
			throw new \RuntimeException('Unable to encrypt the mailbox password.');
		}

		return 'o1:' . base64_encode($nonce . $tag . $cipher);
	}

	/** @return string */
	public function decrypt(?string $encoded): string
	{
		if ($encoded === null || $encoded === '')
		{
			return '';
		}

		$payload = base64_decode(substr($encoded, 3), true);

		if (!is_string($payload))
		{
			return '';
		}

		$key = $this->key();

		if (str_starts_with($encoded, 's1:') && function_exists('sodium_crypto_secretbox_open'))
		{
			$nonceLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
			$plainText = sodium_crypto_secretbox_open(substr($payload, $nonceLength), substr($payload, 0, $nonceLength), $key);

			return is_string($plainText) ? $plainText : '';
		}

		if (str_starts_with($encoded, 'o1:'))
		{
			$nonce = substr($payload, 0, 12);
			$tag = substr($payload, 12, 16);
			$cipher = substr($payload, 28);
			$plainText = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);

			return is_string($plainText) ? $plainText : '';
		}

		return '';
	}

	/** @return string */
	private function key(): string
	{
		$secret = (string) Factory::getConfig()->get('secret');

		return hash('sha256', 'com_pungamail:' . $secret, true);
	}
}
