<?php
/** Regression tests for Automatic Newsletter filter field type metadata. */

require_once __DIR__ . '/../extensions/com_pungamail/administrator/components/com_pungamail/src/Service/ContentTypeService.php';

use Punga\Component\PungaMail\Administrator\Service\ContentTypeService;

$reflection = new ReflectionClass(ContentTypeService::class);
$service = $reflection->newInstanceWithoutConstructor();

$call = static function (string $method, array $args) use ($reflection, $service): mixed
{
	$refMethod = $reflection->getMethod($method);
	$refMethod->setAccessible(true);

	return $refMethod->invokeArgs($service, $args);
};

$cases = [
	['filterKind', ['int unsigned', false], 'number'],
	['filterKind', ['decimal(10,2)', false], 'number'],
	['filterKind', ['tinyint(1)', false], 'boolean'],
	['filterKind', ['date', true], 'date'],
	['filterKind', ['datetime', true], 'date'],
	['filterKind', ['timestamp', true], 'date'],
	['filterKind', ['time', true], 'date'],
	['filterKind', ['varchar(255)', false], 'string'],
	['filterInputKind', ['date', 'date'], 'date'],
	['filterInputKind', ['datetime', 'date'], 'datetime'],
	['filterInputKind', ['timestamp', 'date'], 'datetime'],
	['filterInputKind', ['time', 'date'], 'time'],
	['filterInputKind', ['int unsigned', 'number'], 'number'],
	['filterInputKind', ['varchar(255)', 'string'], 'text'],
	['numberStep', ['int unsigned'], '1'],
	['numberStep', ['decimal(10,2)'], '0.01'],
	['numberStep', ['double'], 'any'],
	['numberMin', ['int unsigned'], '0'],
	['numberMin', ['int'], null],
	['dataTypeName', ['VARCHAR(255)'], 'varchar(255)'],
];

foreach ($cases as [$method, $args, $expected])
{
	$actual = $call($method, $args);

	if ($actual !== $expected)
	{
		throw new RuntimeException($method . ' failed for ' . json_encode($args) . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
	}
}

echo "Filter field type tests passed.\n";
