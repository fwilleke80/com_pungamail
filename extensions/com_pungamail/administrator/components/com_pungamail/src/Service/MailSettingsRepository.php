<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Date\Date;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Stores bounce-mailbox settings without returning the stored password to UI code. */
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

		unset($row->bounce_password_cipher);
		$row->password_configured = $this->hasPassword();

		return $row;
	}

	/** @return object */
	public function getConnection(): object
	{
		$row = $this->load() ?? $this->getPublic();
		$row->bounce_password = $this->secrets->decrypt((string) ($row->bounce_password_cipher ?? ''));
		unset($row->bounce_password_cipher);

		return $row;
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

		if ($address !== '' && !filter_var($address, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException('The bounce address is not a valid email address.');
		}

		$existing = $this->load();
		$cipher = $newPassword !== ''
			? $this->secrets->encrypt($newPassword)
			: (string) ($existing->bounce_password_cipher ?? '');
		$now = (new Date('now', 'UTC'))->toSql();

		if ($existing === null)
		{
			$row = (object) [
				'id' => 1,
				'bounce_host' => $host,
				'bounce_port' => $port,
				'bounce_security' => $security,
				'validate_cert' => $validate,
				'bounce_username' => $username,
				'bounce_password_cipher' => $cipher !== '' ? $cipher : null,
				'bounce_mailbox' => $mailbox,
				'bounce_address' => $address,
				'created' => $now,
				'modified' => $now,
			];
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

	/** @return bool */
	private function hasPassword(): bool
	{
		$row = $this->load();

		return $row !== null && trim((string) ($row->bounce_password_cipher ?? '')) !== '';
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
}
