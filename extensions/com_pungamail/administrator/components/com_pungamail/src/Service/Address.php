<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

/**
 * Email-address normalization helpers.
 */
final class Address
{
	/**
	 * Normalizes an email address for identity and deduplication comparisons.
	 *
	 * Punga Mail intentionally lowercases the full address. Although the local
	 * part is theoretically case-sensitive, contemporary mailbox providers and
	 * Joomla installations treat addresses case-insensitively in practice.
	 *
	 * @param string $email Raw email address.
	 *
	 * @return string Normalized address.
	 */
	public static function normalize(string $email): string
	{
		return mb_strtolower(trim($email), 'UTF-8');
	}
}
