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

/**
 * Coordinates editor locks with Joomla's Global Check-in system.
 */
final class CheckoutService
{
	/** @var array<string,string> */
	private const TABLES = [
		'newsletter' => '#__pungamail_newsletters',
		'template' => '#__pungamail_templates',
		'topic' => '#__pungamail_topics',
		'digest' => '#__pungamail_digests',
	];

	/** @param DatabaseInterface $db Joomla database connection. */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/**
	 * Checks an existing record out to one administrator.
	 *
	 * @param string $entity Entity key from the internal allowlist.
	 * @param int    $id     Record ID.
	 * @param int    $userId Administrator user ID.
	 *
	 * @return void
	 */
	public function checkout(string $entity, int $id, int $userId): void
	{
		if ($id <= 0 || $userId <= 0)
		{
			return;
		}

		$table = $this->table($entity);
		$current = $this->ownerId($table, $id);

		if ($current > 0 && $current !== $userId)
		{
			throw new \RuntimeException(Text::sprintf('COM_PUNGAMAIL_ERROR_CHECKED_OUT_BY', $this->ownerName($current)));
		}

		$now = (new Date('now', 'UTC'))->toSql();
		$free = 0;
		$owner = $userId;
		$recordId = $id;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName($table))
			->set($this->db->quoteName('checked_out') . ' = :owner')
			->set($this->db->quoteName('checked_out_time') . ' = :checkedOutTime')
			->where($this->db->quoteName('id') . ' = :recordId')
			->where('(' . $this->db->quoteName('checked_out') . ' = :free OR ' . $this->db->quoteName('checked_out') . ' = :currentUser)')
			->bind(':owner', $owner, ParameterType::INTEGER)
			->bind(':checkedOutTime', $now)
			->bind(':recordId', $recordId, ParameterType::INTEGER)
			->bind(':free', $free, ParameterType::INTEGER)
			->bind(':currentUser', $userId, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();

		if ($this->db->getAffectedRows() === 0)
		{
			$current = $this->ownerId($table, $id);

			if ($current > 0 && $current !== $userId)
			{
				throw new \RuntimeException(Text::sprintf('COM_PUNGAMAIL_ERROR_CHECKED_OUT_BY', $this->ownerName($current)));
			}
		}
	}

	/**
	 * Releases a record only when it belongs to the current administrator.
	 *
	 * @param string $entity Entity key from the internal allowlist.
	 * @param int    $id     Record ID.
	 * @param int    $userId Administrator user ID.
	 *
	 * @return void
	 */
	public function checkin(string $entity, int $id, int $userId): void
	{
		if ($id <= 0 || $userId <= 0)
		{
			return;
		}

		$table = $this->table($entity);
		$free = 0;
		$recordId = $id;
		$currentUser = $userId;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName($table))
			->set($this->db->quoteName('checked_out') . ' = :free')
			->set($this->db->quoteName('checked_out_time') . ' = NULL')
			->where($this->db->quoteName('id') . ' = :recordId')
			->where($this->db->quoteName('checked_out') . ' = :currentUser')
			->bind(':free', $free, ParameterType::INTEGER)
			->bind(':recordId', $recordId, ParameterType::INTEGER)
			->bind(':currentUser', $currentUser, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}

	/** @return int */
	private function ownerId(string $table, int $id): int
	{
		$recordId = $id;
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('checked_out'))
			->from($this->db->quoteName($table))
			->where($this->db->quoteName('id') . ' = :recordId')
			->bind(':recordId', $recordId, ParameterType::INTEGER);

		return (int) $this->db->setQuery($query)->loadResult();
	}

	/** @return string */
	private function ownerName(int $userId): string
	{
		$id = $userId;
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('name'))
			->from($this->db->quoteName('#__users'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$name = trim((string) $this->db->setQuery($query)->loadResult());

		return $name !== '' ? $name : Text::_('COM_PUNGAMAIL_ANOTHER_ADMINISTRATOR');
	}

	/** @return string */
	private function table(string $entity): string
	{
		if (!isset(self::TABLES[$entity]))
		{
			throw new \InvalidArgumentException('Unsupported Punga Mail checkout entity.');
		}

		return self::TABLES[$entity];
	}
}
