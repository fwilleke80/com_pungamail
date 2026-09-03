<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

/** Sanitizes operational errors before they are stored, logged or displayed. */
final class ErrorMessage
{
	/**
	 * Removes credentials, security tokens and control characters.
	 *
	 * @param \Throwable|string $error  Exception or raw error message.
	 * @param int               $length Maximum returned length.
	 *
	 * @return string Safe diagnostic text.
	 */
	public static function sanitize(\Throwable|string $error, int $length = 4000): string
	{
		$value = $error instanceof \Throwable ? $error->getMessage() : $error;
		$value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? '';
		$value = preg_replace('#([a-z][a-z0-9+.-]*://)([^/@\s]+)@#iu', '$1[redacted]@', $value) ?? $value;
		$value = preg_replace('/([?&](?:token|key|password|passwd|secret|authorization)=)[^&\s]+/iu', '$1[redacted]', $value) ?? $value;
		$value = preg_replace('/\b(password|passwd|pwd|authorization)\s*[:=]\s*(?:"[^"]*"|\'[^\']*\'|[^\s,;]+)/iu', '$1=[redacted]', $value) ?? $value;
		$value = preg_replace('/\bAUTH\s+(?:PLAIN|LOGIN)\s+[^\s]+/iu', 'AUTH [redacted]', $value) ?? $value;
		$value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

		return mb_substr($value, 0, max(1, $length), 'UTF-8');
	}
}
