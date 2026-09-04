<?php
/**
 * @package   Punga.Mail
 * @copyright Copyright (c) 2026 Punga
 * @license   MIT
 */

declare(strict_types=1);

use Punga\Component\PungaMail\Administrator\Service\DigestSchedule;

require_once dirname(__DIR__) . '/extensions/com_pungamail/administrator/components/com_pungamail/src/Service/DigestSchedule.php';

/**
 * Fails the automatic-newsletter schedule regression test.
 *
 * @param string $message Failure description.
 *
 * @return never
 */
function failDigestScheduleTest(string $message): never
{
	fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
	exit(1);
}

$cases = [
	['2026-01-31 10:00:00', 1, 'months', 31, '2026-02-28 10:00:00'],
	['2026-02-28 10:00:00', 1, 'months', 31, '2026-03-31 10:00:00'],
	['2028-01-31 10:00:00', 1, 'months', 31, '2028-02-29 10:00:00'],
	['2026-01-30 10:00:00', 1, 'months', 30, '2026-02-28 10:00:00'],
	['2026-02-28 10:00:00', 1, 'months', 30, '2026-03-30 10:00:00'],
	['2026-09-04 10:00:00', 7, 'days', 0, '2026-09-11 10:00:00'],
	['2026-09-04 10:00:00', 2, 'weeks', 0, '2026-09-18 10:00:00'],
];

foreach ($cases as [$start, $value, $unit, $anchor, $expected])
{
	$actual = DigestSchedule::advance($start, $value, $unit, $anchor);

	if ($actual !== $expected)
	{
		failDigestScheduleTest(sprintf('%s + %d %s produced %s, expected %s', $start, $value, $unit, $actual, $expected));
	}
}

if (DigestSchedule::subtract('2026-03-31 10:00:00', 1, 'months') !== '2026-02-28 10:00:00')
{
	failDigestScheduleTest('Calendar-month cutoff subtraction is incorrect.');
}

if (DigestSchedule::legacyMinutes(1, 'weeks') !== 10080 || DigestSchedule::legacyMinutes(1, 'months') !== 43200)
{
	failDigestScheduleTest('Legacy minute compatibility conversion is incorrect.');
}

fwrite(STDOUT, "[OK] Automatic Newsletter schedule regression tests passed\n");
