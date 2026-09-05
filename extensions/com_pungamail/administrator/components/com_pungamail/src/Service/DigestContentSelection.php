<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

/** Applies deterministic ordering and item-count rules to Automatic Newsletter content. */
final class DigestContentSelection
{
	/**
	 * @param array<int,object> $items        Eligible content items.
	 * @param string            $order        newest or oldest.
	 * @param int               $maxItems     Maximum items; zero means unlimited.
	 * @param int               $minimumItems Minimum required items; zero disables the threshold.
	 *
	 * @return array{items:array<int,object>,available_count:int,minimum_items:int,below_minimum:bool}
	 */
	public static function apply(array $items, string $order, int $maxItems, int $minimumItems): array
	{
		$order = $order === 'oldest' ? 'oldest' : 'newest';
		$maxItems = max(0, min(1000, $maxItems));
		$minimumItems = max(0, min(1000, $minimumItems));

		usort($items, static function (object $a, object $b) use ($order): int
		{
			$comparison = strcmp((string) ($a->published ?? ''), (string) ($b->published ?? ''));

			if ($comparison === 0)
			{
				$comparison = strcmp((string) ($a->title ?? ''), (string) ($b->title ?? ''));
			}

			return $order === 'oldest' ? $comparison : -$comparison;
		});

		$availableCount = count($items);
		$belowMinimum = $minimumItems > 0 && $availableCount < $minimumItems;

		if (!$belowMinimum && $maxItems > 0 && $availableCount > $maxItems)
		{
			$items = array_slice($items, 0, $maxItems);
		}

		return [
			'items' => $items,
			'available_count' => $availableCount,
			'minimum_items' => $minimumItems,
			'below_minimum' => $belowMinimum,
		];
	}
}
