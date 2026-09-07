<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Resolves published Joomla User Custom Fields for recipient personalization.
 */
final class UserFieldService
{
	/**
	 * @param DatabaseInterface $db Joomla database connection.
	 */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/**
	 * Returns insertable placeholders for all published Joomla User Custom Fields.
	 *
	 * @return array<int,string> Placeholders in `{userfield|field-name}` form.
	 */
	public function placeholders(): array
	{
		try
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('name'))
				->from($this->db->quoteName('#__fields'))
				->where($this->db->quoteName('context') . ' = ' . $this->db->quote('com_users.user'))
				->where($this->db->quoteName('state') . ' = 1')
				->order($this->db->quoteName('ordering') . ' ASC')
				->order($this->db->quoteName('id') . ' ASC');
			$names = array_map('strval', $this->db->setQuery($query)->loadColumn());
		}
		catch (\Throwable)
		{
			return [];
		}

		$placeholders = [];

		foreach ($names as $name)
		{
			$name = trim($name);

			if ($name !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $name) === 1)
			{
				$placeholders[] = '{userfield|' . $name . '}';
			}
		}

		return array_values(array_unique($placeholders));
	}

	/**
	 * Resolves published Joomla User Custom Field values by field name.
	 *
	 * @param int|null $userId Joomla user ID, or null for an external subscriber.
	 *
	 * @return array<string,string> Field-name-to-value map.
	 */
	public function valuesForUser(?int $userId): array
	{
		if ($userId === null || $userId <= 0)
		{
			return [];
		}

		try
		{
			$query = $this->db->getQuery(true)
				->select([
					$this->db->quoteName('f.name', 'name'),
					$this->db->quoteName('v.value', 'value'),
				])
				->from($this->db->quoteName('#__fields', 'f'))
				->leftJoin(
					$this->db->quoteName('#__fields_values', 'v')
					. ' ON ' . $this->db->quoteName('v.field_id') . ' = ' . $this->db->quoteName('f.id')
					. ' AND ' . $this->db->quoteName('v.item_id') . ' = :userId'
				)
				->where($this->db->quoteName('f.context') . ' = ' . $this->db->quote('com_users.user'))
				->where($this->db->quoteName('f.state') . ' = 1')
				->bind(':userId', $userId, ParameterType::INTEGER);
			$rows = $this->db->setQuery($query)->loadObjectList();
		}
		catch (\Throwable)
		{
			return [];
		}

		$values = [];

		foreach ($rows as $row)
		{
			$name = trim((string) ($row->name ?? ''));

			if ($name === '' || preg_match('/^[A-Za-z0-9_-]+$/', $name) !== 1)
			{
				continue;
			}

			$value = $this->normalizeValue($row->value ?? null);
			$key = strtolower($name);

			if ($value === '')
			{
				$values[$key] ??= '';
				continue;
			}

			if (isset($values[$key]) && $values[$key] !== '')
			{
				$values[$key] .= ', ' . $value;
			}
			else
			{
				$values[$key] = $value;
			}
		}

		return $values;
	}

	/**
	 * Converts Joomla's stored field representation to useful mail text.
	 *
	 * @param mixed $value Stored field value.
	 *
	 * @return string Normalized recipient value.
	 */
	private function normalizeValue(mixed $value): string
	{
		if ($value === null)
		{
			return '';
		}

		if (!is_scalar($value))
		{
			return '';
		}

		$text = trim((string) $value);

		if ($text === '' || ($text[0] ?? '') !== '[' && ($text[0] ?? '') !== '{')
		{
			return $text;
		}

		$decoded = json_decode($text, true);

		if (!is_array($decoded))
		{
			return $text;
		}

		$flat = [];
		array_walk_recursive($decoded, static function (mixed $item) use (&$flat): void
		{
			if (is_scalar($item) && trim((string) $item) !== '')
			{
				$flat[] = trim((string) $item);
			}
		});

		return implode(', ', $flat);
	}
}
