<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use DateTimeImmutable;
use DateTimeZone;

/** Calendar-aware recurrence calculations for automatic newsletters. */
final class DigestSchedule
{
	public const UNIT_DAYS = 'days';
	public const UNIT_WEEKS = 'weeks';
	public const UNIT_MONTHS = 'months';

	/**
	 * Normalizes a recurrence unit.
	 *
	 * @param string $unit Submitted recurrence unit.
	 *
	 * @return string Safe recurrence unit.
	 */
	public static function normalizeUnit(string $unit): string
	{
		return in_array($unit, [self::UNIT_DAYS, self::UNIT_WEEKS, self::UNIT_MONTHS], true)
			? $unit
			: self::UNIT_WEEKS;
	}

	/**
	 * Normalizes a recurrence value.
	 *
	 * @param int $value Submitted recurrence value.
	 *
	 * @return int Safe positive recurrence value.
	 */
	public static function normalizeValue(int $value): int
	{
		return max(1, min(365, $value));
	}

	/**
	 * Returns a legacy minute approximation retained for compatibility.
	 * Calendar-month scheduling never uses this value.
	 *
	 * @param int    $value Recurrence value.
	 * @param string $unit  Recurrence unit.
	 *
	 * @return int Approximate number of minutes.
	 */
	public static function legacyMinutes(int $value, string $unit): int
	{
		$value = self::normalizeValue($value);
		$unit = self::normalizeUnit($unit);
		$minutesPerUnit = match ($unit)
		{
			self::UNIT_DAYS => 1440,
			self::UNIT_WEEKS => 10080,
			self::UNIT_MONTHS => 43200,
		};

		return min(525600, $value * $minutesPerUnit);
	}

	/**
	 * Returns the day-of-month used to anchor calendar-month recurrence.
	 *
	 * @param string $sqlDate UTC SQL date/time.
	 *
	 * @return int Day of month from 1 through 31.
	 */
	public static function anchorDay(string $sqlDate): int
	{
		$date = new DateTimeImmutable($sqlDate, new DateTimeZone('UTC'));

		return (int) $date->format('j');
	}

	/**
	 * Advances one scheduled occurrence.
	 *
	 * @param string $sqlDate   Current UTC SQL date/time.
	 * @param int    $value     Recurrence value.
	 * @param string $unit      Recurrence unit.
	 * @param int    $anchorDay Original day of month for monthly recurrence.
	 *
	 * @return string Next UTC SQL date/time.
	 */
	public static function advance(string $sqlDate, int $value, string $unit, int $anchorDay = 0): string
	{
		$date = new DateTimeImmutable($sqlDate, new DateTimeZone('UTC'));
		$value = self::normalizeValue($value);
		$unit = self::normalizeUnit($unit);

		$date = match ($unit)
		{
			self::UNIT_DAYS => $date->modify('+' . $value . ' days'),
			self::UNIT_WEEKS => $date->modify('+' . $value . ' weeks'),
			self::UNIT_MONTHS => self::shiftMonths($date, $value, $anchorDay > 0 ? $anchorDay : (int) $date->format('j')),
		};

		return $date->format('Y-m-d H:i:s');
	}

	/**
	 * Moves backwards by one recurrence interval for the initial content cutoff.
	 *
	 * @param string $sqlDate UTC SQL date/time.
	 * @param int    $value   Recurrence value.
	 * @param string $unit    Recurrence unit.
	 *
	 * @return string Earlier UTC SQL date/time.
	 */
	public static function subtract(string $sqlDate, int $value, string $unit): string
	{
		$date = new DateTimeImmutable($sqlDate, new DateTimeZone('UTC'));
		$value = self::normalizeValue($value);
		$unit = self::normalizeUnit($unit);

		$date = match ($unit)
		{
			self::UNIT_DAYS => $date->modify('-' . $value . ' days'),
			self::UNIT_WEEKS => $date->modify('-' . $value . ' weeks'),
			self::UNIT_MONTHS => self::shiftMonths($date, -$value, (int) $date->format('j')),
		};

		return $date->format('Y-m-d H:i:s');
	}

	/**
	 * Shifts a date by calendar months while retaining an intended day of month.
	 * If that day does not exist in the target month, the target month's final
	 * day is used; the original anchor is retained for later occurrences.
	 *
	 * @param DateTimeImmutable $date      Source date.
	 * @param int               $months    Signed month offset.
	 * @param int               $anchorDay Intended day of month.
	 *
	 * @return DateTimeImmutable Shifted date.
	 */
	private static function shiftMonths(DateTimeImmutable $date, int $months, int $anchorDay): DateTimeImmutable
	{
		$anchorDay = max(1, min(31, $anchorDay));
		$target = $date->modify('first day of this month')->modify(($months >= 0 ? '+' : '') . $months . ' months');
		$day = min($anchorDay, (int) $target->format('t'));

		return $target->setDate((int) $target->format('Y'), (int) $target->format('n'), $day);
	}
}
