<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

/**
 * Resolves the human-readable value used by the `{recipient}` placeholder.
 */
final class RecipientName
{
	/**
	 * Prefer a Joomla user's display name and fall back to the email address.
	 *
	 * @param string $name  Joomla user display name, if known.
	 * @param string $email Recipient email address.
	 *
	 * @return string
	 */
	public static function resolve(string $name, string $email): string
	{
		$name = trim($name);

		return $name !== '' ? $name : trim($email);
	}
}
