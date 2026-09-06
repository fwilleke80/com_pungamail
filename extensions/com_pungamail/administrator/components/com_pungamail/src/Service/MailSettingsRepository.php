<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Stores encrypted outgoing-mail and returned-mail account settings. */
final class MailSettingsRepository
{
	/**
	 * @param DatabaseInterface $db      Database connection.
	 * @param SecretService     $secrets Secret encryption service.
	 */
	public function __construct(
		private readonly DatabaseInterface $db,
		private readonly SecretService $secrets
	)
	{
	}

	/** @return object */
	public function getPublic(): object
	{
		$row = $this->load();

		if ($row === null)
		{
			return (object) [
				'bounce_host' => '',
				'bounce_port' => 993,
				'bounce_security' => 'ssl',
				'validate_cert' => 1,
				'bounce_username' => '',
				'bounce_mailbox' => 'INBOX',
				'bounce_address' => '',
				'password_configured' => false,
			];
		}

		unset(
			$row->bounce_password_cipher,
			$row->smtp_password_cipher,
			$row->bounce_last_check_result,
			$row->bounce_last_check_error
		);
		$row->password_configured = $this->hasBouncePassword();

		return $row;
	}

	/** @return object */
	public function getOutgoingPublic(): object
	{
		$row = $this->load();

		if ($row === null)
		{
			return (object) [
				'smtp_mode' => 'joomla',
				'smtp_host' => '',
				'smtp_port' => 587,
				'smtp_security' => 'tls',
				'smtp_auth' => 1,
				'smtp_username' => '',
				'password_configured' => false,
			];
		}

		return (object) [
			'smtp_mode' => in_array((string) ($row->smtp_mode ?? ''), ['joomla', 'custom'], true)
				? (string) $row->smtp_mode
				: 'joomla',
			'smtp_host' => (string) ($row->smtp_host ?? ''),
			'smtp_port' => (int) ($row->smtp_port ?? 587),
			'smtp_security' => (string) ($row->smtp_security ?? 'tls'),
			'smtp_auth' => (int) ($row->smtp_auth ?? 1),
			'smtp_username' => (string) ($row->smtp_username ?? ''),
			'password_configured' => $this->hasOutgoingPassword(),
		];
	}

	/**
	 * Returns the most recent returned-mail check summary.
	 *
	 * @return object|null Last check summary, or null before the first check.
	 */
	public function getBounceCheck(): ?object
	{
		$row = $this->load();

		if ($row === null || empty($row->bounce_last_check_at))
		{
			return null;
		}

		$result = json_decode((string) ($row->bounce_last_check_result ?? ''), true);

		if (!is_array($result))
		{
			$result = [];
		}

		return (object) [
			'checked_at' => (string) $row->bounce_last_check_at,
			'ok' => (string) ($row->bounce_last_check_status ?? '') === 'ok',
			'checked' => (int) ($result['checked'] ?? 0),
			'processed' => (int) ($result['processed'] ?? 0),
			'hard' => (int) ($result['hard'] ?? 0),
			'soft' => (int) ($result['soft'] ?? 0),
			'unknown' => (int) ($result['unknown'] ?? 0),
			'duplicates' => (int) ($result['duplicates'] ?? 0),
			'suppressed' => (int) ($result['suppressed'] ?? 0),
			'error' => trim((string) ($row->bounce_last_check_error ?? '')),
		];
	}

	/**
	 * Stores the most recent returned-mail check summary for administrator UI.
	 *
	 * @param array<string,int> $result Check counters.
	 * @param string|null       $error  Sanitized failure message, or null on success.
	 *
	 * @return void
	 */
	public function recordBounceCheck(array $result, ?string $error = null): void
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$status = $error === null ? 'ok' : 'failed';
		$payload = json_encode([
			'checked' => (int) ($result['checked'] ?? 0),
			'processed' => (int) ($result['processed'] ?? 0),
			'hard' => (int) ($result['hard'] ?? 0),
			'soft' => (int) ($result['soft'] ?? 0),
			'unknown' => (int) ($result['unknown'] ?? 0),
			'duplicates' => (int) ($result['duplicates'] ?? 0),
			'suppressed' => (int) ($result['suppressed'] ?? 0),
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$payloadValue = $payload !== false ? $payload : '{}';
		$existing = $this->load();

		if ($existing === null)
		{
			$row = $this->defaultRow($now);
			$row->bounce_last_check_at = $now;
			$row->bounce_last_check_status = $status;
			$row->bounce_last_check_result = $payloadValue;
			$row->bounce_last_check_error = $error !== null && $error !== '' ? $error : null;
			$this->db->insertObject('#__pungamail_mail_settings', $row);
			return;
		}

		$id = 1;
		$errorValue = $error !== null && $error !== '' ? $error : null;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_mail_settings'))
			->set($this->db->quoteName('bounce_last_check_at') . ' = :checkedAt')
			->set($this->db->quoteName('bounce_last_check_status') . ' = :status')
			->set($this->db->quoteName('bounce_last_check_result') . ' = :result')
			->set($this->db->quoteName('bounce_last_check_error') . ($errorValue === null ? ' = NULL' : ' = :error'))
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':checkedAt', $now)
			->bind(':status', $status)
			->bind(':result', $payloadValue)
			->bind(':modified', $now)
			->bind(':id', $id, ParameterType::INTEGER);

		if ($errorValue !== null)
		{
			$query->bind(':error', $errorValue);
		}

		$this->db->setQuery($query)->execute();
	}

	/** @return object */
	public function getConnection(): object
	{
		$row = $this->load() ?? $this->defaultRow((new Date('now', 'UTC'))->toSql());
		$row->bounce_password = $this->secrets->decrypt((string) ($row->bounce_password_cipher ?? ''));
		unset($row->bounce_password_cipher, $row->smtp_password_cipher);

		return $row;
	}

	/** @return object */
	public function getOutgoingConnection(): object
	{
		$row = $this->load();
		$public = $this->getOutgoingPublic();
		$public->smtp_password = $row === null
			? ''
			: $this->secrets->decrypt((string) ($row->smtp_password_cipher ?? ''));

		return $public;
	}

	/**
	 * Resolves posted outgoing settings for a connection test without saving them.
	 * A blank posted password keeps using the encrypted saved password.
	 *
	 * @param array<string,mixed> $data        Posted SMTP fields.
	 * @param string              $newPassword Newly entered password.
	 * @return object Validated transport settings.
	 */
	public function outgoingFromInput(array $data, string $newPassword): object
	{
		$settings = $this->normalizeOutgoing($data);
		$existing = $this->load();
		$settings->smtp_password = $newPassword !== ''
			? $newPassword
			: $this->secrets->decrypt((string) ($existing->smtp_password_cipher ?? ''));
		$this->validateOutgoingReady($settings);

		return $settings;
	}

	/** @return void */
	public function save(array $data, string $newPassword): void
	{
		$host = trim((string) ($data['bounce_host'] ?? ''));
		$port = max(1, min(65535, (int) ($data['bounce_port'] ?? 993)));
		$security = in_array((string) ($data['bounce_security'] ?? ''), ['ssl', 'tls', 'none'], true)
			? (string) $data['bounce_security']
			: 'ssl';
		$validate = (int) ($data['validate_cert'] ?? 0) === 1 ? 1 : 0;
		$username = trim((string) ($data['bounce_username'] ?? ''));
		$mailbox = trim((string) ($data['bounce_mailbox'] ?? 'INBOX')) ?: 'INBOX';
		$address = trim((string) ($data['bounce_address'] ?? ''));

		if ($host !== '' && !$this->isValidHost($host))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_BOUNCE_HOST_INVALID'));
		}

		if (preg_match('/[\x00-\x1F\x7F{}]/', $mailbox) === 1 || mb_strlen($mailbox, 'UTF-8') > 191)
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_BOUNCE_MAILBOX_INVALID'));
		}

		if (!$this->isValidUsername($username))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_BOUNCE_USERNAME_INVALID'));
		}

		if ($address !== '' && !filter_var($address, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_BOUNCE_ADDRESS_INVALID'));
		}

		$existing = $this->load();
		$cipher = $newPassword !== ''
			? $this->secrets->encrypt($newPassword)
			: (string) ($existing->bounce_password_cipher ?? '');
		$now = (new Date('now', 'UTC'))->toSql();

		if ($existing === null)
		{
			$row = $this->defaultRow($now);
			$row->bounce_host = $host;
			$row->bounce_port = $port;
			$row->bounce_security = $security;
			$row->validate_cert = $validate;
			$row->bounce_username = $username;
			$row->bounce_password_cipher = $cipher !== '' ? $cipher : null;
			$row->bounce_mailbox = $mailbox;
			$row->bounce_address = $address;
			$this->db->insertObject('#__pungamail_mail_settings', $row);
			return;
		}

		$id = 1;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_mail_settings'))
			->set($this->db->quoteName('bounce_host') . ' = :host')
			->set($this->db->quoteName('bounce_port') . ' = :port')
			->set($this->db->quoteName('bounce_security') . ' = :security')
			->set($this->db->quoteName('validate_cert') . ' = :validate')
			->set($this->db->quoteName('bounce_username') . ' = :username')
			->set($this->db->quoteName('bounce_password_cipher') . ($cipher === '' ? ' = NULL' : ' = :cipher'))
			->set($this->db->quoteName('bounce_mailbox') . ' = :mailbox')
			->set($this->db->quoteName('bounce_address') . ' = :address')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':host', $host)
			->bind(':port', $port, ParameterType::INTEGER)
			->bind(':security', $security)
			->bind(':validate', $validate, ParameterType::INTEGER)
			->bind(':username', $username)
			->bind(':mailbox', $mailbox)
			->bind(':address', $address)
			->bind(':modified', $now)
			->bind(':id', $id, ParameterType::INTEGER);

		if ($cipher !== '')
		{
			$query->bind(':cipher', $cipher);
		}

		$this->db->setQuery($query)->execute();
	}

	/**
	 * Stores the Punga Mail outgoing transport independently from Joomla's global mail settings.
	 *
	 * @param array<string,mixed> $data        Posted SMTP fields.
	 * @param string              $newPassword Newly entered password, or blank to keep the saved secret.
	 * @return void
	 */
	public function saveOutgoing(array $data, string $newPassword): void
	{
		$settings = $this->normalizeOutgoing($data);
		$existing = $this->load();
		$cipher = $newPassword !== ''
			? $this->secrets->encrypt($newPassword)
			: (string) ($existing->smtp_password_cipher ?? '');
		$settings->smtp_password = $newPassword !== ''
			? $newPassword
			: $this->secrets->decrypt($cipher);
		$this->validateOutgoingReady($settings);
		$now = (new Date('now', 'UTC'))->toSql();

		if ($existing === null)
		{
			$row = $this->defaultRow($now);
			$row->smtp_mode = $settings->smtp_mode;
			$row->smtp_host = $settings->smtp_host;
			$row->smtp_port = $settings->smtp_port;
			$row->smtp_security = $settings->smtp_security;
			$row->smtp_auth = $settings->smtp_auth;
			$row->smtp_username = $settings->smtp_username;
			$row->smtp_password_cipher = $cipher !== '' ? $cipher : null;
			$this->db->insertObject('#__pungamail_mail_settings', $row);
			return;
		}

		$id = 1;
		$mode = (string) $settings->smtp_mode;
		$host = (string) $settings->smtp_host;
		$port = (int) $settings->smtp_port;
		$security = (string) $settings->smtp_security;
		$auth = (int) $settings->smtp_auth;
		$username = (string) $settings->smtp_username;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_mail_settings'))
			->set($this->db->quoteName('smtp_mode') . ' = :mode')
			->set($this->db->quoteName('smtp_host') . ' = :host')
			->set($this->db->quoteName('smtp_port') . ' = :port')
			->set($this->db->quoteName('smtp_security') . ' = :security')
			->set($this->db->quoteName('smtp_auth') . ' = :auth')
			->set($this->db->quoteName('smtp_username') . ' = :username')
			->set($this->db->quoteName('smtp_password_cipher') . ($cipher === '' ? ' = NULL' : ' = :cipher'))
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':mode', $mode)
			->bind(':host', $host)
			->bind(':port', $port, ParameterType::INTEGER)
			->bind(':security', $security)
			->bind(':auth', $auth, ParameterType::INTEGER)
			->bind(':username', $username)
			->bind(':modified', $now)
			->bind(':id', $id, ParameterType::INTEGER);

		if ($cipher !== '')
		{
			$query->bind(':cipher', $cipher);
		}

		$this->db->setQuery($query)->execute();
	}

	/** @return bool */
	private function hasBouncePassword(): bool
	{
		$row = $this->load();

		return $row !== null && trim((string) ($row->bounce_password_cipher ?? '')) !== '';
	}

	/** @return bool */
	private function hasOutgoingPassword(): bool
	{
		$row = $this->load();

		return $row !== null && trim((string) ($row->smtp_password_cipher ?? '')) !== '';
	}

	/** @return object|null */
	private function load(): ?object
	{
		$id = 1;
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_mail_settings'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/**
	 * @param array<string,mixed> $data Posted SMTP data.
	 * @return object Normalized settings.
	 */
	private function normalizeOutgoing(array $data): object
	{
		$mode = in_array((string) ($data['smtp_mode'] ?? ''), ['joomla', 'custom'], true)
			? (string) $data['smtp_mode']
			: 'joomla';
		$host = trim((string) ($data['smtp_host'] ?? ''));
		$port = max(1, min(65535, (int) ($data['smtp_port'] ?? 587)));
		$security = in_array((string) ($data['smtp_security'] ?? ''), ['ssl', 'tls', 'none'], true)
			? (string) $data['smtp_security']
			: 'tls';
		$auth = (int) ($data['smtp_auth'] ?? 0) === 1 ? 1 : 0;
		$username = trim((string) ($data['smtp_username'] ?? ''));

		if ($host !== '' && !$this->isValidHost($host))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_SMTP_HOST_INVALID'));
		}

		if (!$this->isValidUsername($username))
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_SMTP_USERNAME_INVALID'));
		}

		return (object) [
			'smtp_mode' => $mode,
			'smtp_host' => $host,
			'smtp_port' => $port,
			'smtp_security' => $security,
			'smtp_auth' => $auth,
			'smtp_username' => $username,
		];
	}

	/** @param object $settings Normalized outgoing settings. @return void */
	private function validateOutgoingReady(object $settings): void
	{
		if ((string) $settings->smtp_mode !== 'custom')
		{
			return;
		}

		if (trim((string) $settings->smtp_host) === '')
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_SMTP_HOST_REQUIRED'));
		}

		if ((int) $settings->smtp_auth === 1 && trim((string) $settings->smtp_username) === '')
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_SMTP_USERNAME_REQUIRED'));
		}

		if ((int) $settings->smtp_auth === 1 && (string) ($settings->smtp_password ?? '') === '')
		{
			throw new \InvalidArgumentException(Text::_('COM_PUNGAMAIL_SMTP_PASSWORD_REQUIRED'));
		}
	}

	/** @param string $now UTC SQL timestamp. @return object */
	private function defaultRow(string $now): object
	{
		return (object) [
			'id' => 1,
			'smtp_mode' => 'joomla',
			'smtp_host' => '',
			'smtp_port' => 587,
			'smtp_security' => 'tls',
			'smtp_auth' => 1,
			'smtp_username' => '',
			'smtp_password_cipher' => null,
			'bounce_host' => '',
			'bounce_port' => 993,
			'bounce_security' => 'ssl',
			'validate_cert' => 1,
			'bounce_username' => '',
			'bounce_password_cipher' => null,
			'bounce_mailbox' => 'INBOX',
			'bounce_address' => '',
			'bounce_last_check_at' => null,
			'bounce_last_check_status' => '',
			'bounce_last_check_result' => null,
			'bounce_last_check_error' => null,
			'created' => $now,
			'modified' => $now,
		];
	}

	/** @return bool */
	private function isValidHost(string $host): bool
	{
		if (preg_match('/[\x00-\x20\x7F{}\/]/', $host) === 1)
		{
			return false;
		}

		$ipHost = trim($host, '[]');

		return filter_var($ipHost, FILTER_VALIDATE_IP) !== false
			|| filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
	}

	/** @return bool */
	private function isValidUsername(string $username): bool
	{
		return preg_match('/[\r\n\x00]/', $username) !== 1 && mb_strlen($username, 'UTF-8') <= 320;
	}
}
