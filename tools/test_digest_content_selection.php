<?php
/** Regression tests for Automatic Newsletter content ordering and limits. */

declare(strict_types=1);

require_once __DIR__ . '/../extensions/com_pungamail/administrator/components/com_pungamail/src/Service/DigestContentSelection.php';

use Punga\Component\PungaMail\Administrator\Service\DigestContentSelection;

/** @param mixed $actual */
function assertSameValue(mixed $actual, mixed $expected, string $message): void
{
	if ($actual !== $expected)
	{
		fwrite(STDERR, $message . PHP_EOL);
		fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
		fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
		exit(1);
	}
}

$items = [
	(object) ['title' => 'Middle', 'published' => '2026-09-02 10:00:00'],
	(object) ['title' => 'Newest', 'published' => '2026-09-03 10:00:00'],
	(object) ['title' => 'Oldest', 'published' => '2026-09-01 10:00:00'],
];

$newest = DigestContentSelection::apply($items, 'newest', 2, 0);
assertSameValue(array_map(static fn (object $item): string => $item->title, $newest['items']), ['Newest', 'Middle'], 'Newest-first ordering or maximum-item limit failed.');
assertSameValue($newest['available_count'], 3, 'Available count must be recorded before applying the maximum.');
assertSameValue($newest['below_minimum'], false, 'Minimum threshold should be disabled at zero.');

$oldest = DigestContentSelection::apply($items, 'oldest', 0, 0);
assertSameValue(array_map(static fn (object $item): string => $item->title, $oldest['items']), ['Oldest', 'Middle', 'Newest'], 'Oldest-first ordering failed.');

$below = DigestContentSelection::apply($items, 'newest', 1, 4);
assertSameValue($below['below_minimum'], true, 'Minimum threshold should reject too-small content sets.');
assertSameValue(count($below['items']), 3, 'Maximum limit must not hide the true item count when the minimum threshold fails.');

fwrite(STDOUT, "Automatic Newsletter content-selection tests passed.\n");
