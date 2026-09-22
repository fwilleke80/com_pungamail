<?php
/** Regression tests for generic Automatic Newsletter content filters. */

require_once __DIR__ . '/../extensions/com_pungamail/administrator/components/com_pungamail/src/Service/DigestContentFilter.php';

use Punga\Component\PungaMail\Administrator\Service\DigestContentFilter;

$items = [
	(object) ['source_key' => 'com_example.item', 'title' => 'A', 'catid' => 2, 'raw_fields' => ['calendar_id' => 1, 'featured' => 1, 'summary' => 'Garden party']],
	(object) ['source_key' => 'com_example.item', 'title' => 'B', 'catid' => 3, 'raw_fields' => ['calendar_id' => 2, 'featured' => 0, 'summary' => 'Maintenance']],
	(object) ['source_key' => 'com_example.item', 'title' => 'C', 'catid' => 2, 'raw_fields' => ['calendar_id' => 3, 'featured' => 1, 'summary' => 'Garden cleanup']],
];

$filters = DigestContentFilter::normalize([
	'com_example.item' => [
		['field' => 'catid', 'operator' => 'in', 'value' => ['2']],
		['field' => 'calendar_id', 'operator' => 'in', 'value' => ['1', '3']],
		['field' => 'summary', 'operator' => 'contains', 'value' => 'garden'],
	],
]);
$result = DigestContentFilter::apply($items, $filters);

if (count($result) !== 2 || $result[0]->title !== 'A' || $result[1]->title !== 'C')
{
	throw new RuntimeException('Generic digest filters did not combine per-source rules with AND semantics.');
}

$excluded = DigestContentFilter::apply($items, ['com_example.item' => [['field' => 'featured', 'operator' => 'eq', 'value' => '0']]]);

if (count($excluded) !== 1 || $excluded[0]->title !== 'B')
{
	throw new RuntimeException('Generic digest filters did not resolve raw content-table field values.');
}

$empty = DigestContentFilter::apply([(object) ['source_key' => 'com_example.item', 'raw_fields' => ['room' => null]]], ['com_example.item' => [['field' => 'room', 'operator' => 'is_empty', 'value' => '']]]);

if (count($empty) !== 1)
{
	throw new RuntimeException('Generic digest empty-value filter failed.');
}

echo "Digest content filter tests passed.\n";
