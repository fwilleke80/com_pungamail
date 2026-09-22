<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

/** Applies content-type-agnostic field filters to Automatic Newsletter candidates. */
final class DigestContentFilter
{
	/** @var array<int,string> */
	private const OPERATORS = [
		'eq', 'neq', 'in', 'not_in', 'contains', 'not_contains',
		'gt', 'gte', 'lt', 'lte', 'is_empty', 'not_empty',
	];

	/**
	 * @param array<int,object> $items   Candidate items.
	 * @param array<string,array<int,array<string,mixed>>> $filters Rules keyed by source key.
	 *
	 * @return array<int,object>
	 */
	public static function apply(array $items, array $filters): array
	{
		if ($filters === [])
		{
			return $items;
		}

		return array_values(array_filter($items, static function (object $item) use ($filters): bool
		{
			$rules = $filters[(string) ($item->source_key ?? '')] ?? [];

			foreach ($rules as $rule)
			{
				if (!self::matches($item, $rule))
				{
					return false;
				}
			}

			return true;
		}));
	}

	/** @return array<string,array<int,array{field:string,operator:string,value:mixed}>> */
	public static function normalize(array $filters): array
	{
		$result = [];

		foreach ($filters as $sourceKey => $rules)
		{
			$sourceKey = trim((string) $sourceKey);

			if ($sourceKey === '' || !is_array($rules))
			{
				continue;
			}

			foreach ($rules as $rule)
			{
				if (!is_array($rule))
				{
					continue;
				}

				$field = trim((string) ($rule['field'] ?? ''));
				$operator = trim((string) ($rule['operator'] ?? 'eq'));

				if (preg_match('/^[A-Za-z0-9_]+$/', $field) !== 1 || !in_array($operator, self::OPERATORS, true))
				{
					continue;
				}

				$value = $rule['value'] ?? '';

				if (is_array($value))
				{
					$value = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $value), static fn (string $item): bool => $item !== ''));

					if (!in_array($operator, ['in', 'not_in'], true))
					{
						$value = $value[0] ?? '';
					}
				}
				else
				{
					$value = trim((string) $value);
				}

				$result[$sourceKey][] = ['field' => $field, 'operator' => $operator, 'value' => $value];
			}
		}

		return $result;
	}

	/** @return bool */
	private static function matches(object $item, array $rule): bool
	{
		$field = (string) ($rule['field'] ?? '');
		$operator = (string) ($rule['operator'] ?? 'eq');
		$expected = $rule['value'] ?? '';
		$actual = self::value($item, $field);

		if ($operator === 'is_empty')
		{
			return self::emptyValue($actual);
		}

		if ($operator === 'not_empty')
		{
			return !self::emptyValue($actual);
		}

		if ($operator === 'in' || $operator === 'not_in')
		{
			$values = self::listValues($expected);
			$found = false;

			foreach ($values as $value)
			{
				if (self::compare($actual, $value) === 0)
				{
					$found = true;
					break;
				}
			}

			return $operator === 'in' ? $found : !$found;
		}

		if ($operator === 'contains' || $operator === 'not_contains')
		{
			$found = stripos((string) $actual, (string) $expected) !== false;
			return $operator === 'contains' ? $found : !$found;
		}

		$comparison = self::compare($actual, $expected);

		return match ($operator)
		{
			'eq' => $comparison === 0,
			'neq' => $comparison !== 0,
			'gt' => $comparison > 0,
			'gte' => $comparison >= 0,
			'lt' => $comparison < 0,
			'lte' => $comparison <= 0,
			default => true,
		};
	}

	/** @return mixed */
	private static function value(object $item, string $field)
	{
		if (property_exists($item, $field))
		{
			return $item->{$field};
		}

		$raw = (array) ($item->raw_fields ?? []);

		return array_key_exists($field, $raw) ? $raw[$field] : null;
	}

	/** @return bool */
	private static function emptyValue(mixed $value): bool
	{
		return $value === null || (is_string($value) && trim($value) === '');
	}

	/** @return array<int,string> */
	private static function listValues(mixed $value): array
	{
		if (is_array($value))
		{
			return array_values(array_map('strval', $value));
		}

		$value = trim((string) $value);

		if ($value === '')
		{
			return [];
		}

		return array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $value) ?: []), static fn (string $item): bool => $item !== ''));
	}

	/** @return int */
	private static function compare(mixed $left, mixed $right): int
	{
		if (is_numeric($left) && is_numeric($right))
		{
			return ((float) $left) <=> ((float) $right);
		}

		return strcmp((string) $left, (string) $right);
	}
}
