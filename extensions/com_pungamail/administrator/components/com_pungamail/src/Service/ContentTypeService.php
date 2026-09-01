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
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

/**
 * Discovers and reads usable Joomla registered content types.
 *
 * Punga Mail intentionally uses the content-type registry only as metadata.
 * It reads the registered component table directly and does not depend on
 * Joomla's deprecated UCM persistence classes or #__ucm_content rows.
 */
final class ContentTypeService
{
	/** @var array<string,object>|null */
	private ?array $types = null;

	/**
	 * @param DatabaseInterface $db Database connection.
	 */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/**
	 * Returns content types whose registered table/field mapping is sufficient
	 * for newsletter discovery.
	 *
	 * @return array<string,object> Types keyed by type_alias.
	 */
	public function getTypes(): array
	{
		if ($this->types !== null)
		{
			return $this->types;
		}

		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('type_title'),
				$this->db->quoteName('type_alias'),
				$this->db->quoteName('table'),
				$this->db->quoteName('field_mappings'),
				$this->db->quoteName('router'),
			])
			->from($this->db->quoteName('#__content_types'))
			->order($this->db->quoteName('type_title') . ' ASC');
		$rows = $this->db->setQuery($query)->loadObjectList();
		$result = [];

		foreach ($rows as $row)
		{
			$type = $this->normalizeType($row);

			if ($type === null)
			{
				continue;
			}

			$result[$type->key] = $type;
		}

		$this->types = $result;

		return $result;
	}

	/**
	 * Returns newly published items from selected registered content types.
	 *
	 * @param array<int,string> $sourceKeys Selected content type aliases.
	 * @param string|null       $cutoff     UTC SQL lower bound.
	 * @param int               $limit      Maximum combined candidates.
	 *
	 * @return array<int,object> Normalized candidates, newest first.
	 */
	public function getItems(array $sourceKeys, ?string $cutoff, int $limit = 500): array
	{
		$types = $this->getTypes();
		$items = [];
		$limit = max(1, min(1000, $limit));

		foreach (array_values(array_unique($sourceKeys)) as $sourceKey)
		{
			if (!isset($types[$sourceKey]))
			{
				continue;
			}

			foreach ($this->queryType($types[$sourceKey], $cutoff, $limit) as $item)
			{
				$items[] = $item;
			}
		}

		usort(
			$items,
			static fn (object $a, object $b): int => strcmp((string) $b->published, (string) $a->published)
		);

		return array_slice($items, 0, $limit);
	}

	/**
	 * Finds one current source item.
	 *
	 * @param string $sourceKey Registered type alias.
	 * @param string $itemId    Registered table key.
	 *
	 * @return object|null Normalized item.
	 */
	public function findItem(string $sourceKey, string $itemId): ?object
	{
		$type = $this->getTypes()[$sourceKey] ?? null;

		if ($type === null || $itemId === '')
		{
			return null;
		}

		$items = $this->queryType($type, null, 1, $itemId);

		return $items[0] ?? null;
	}

	/**
	 * Builds an absolute site URL using the conventional component/view route
	 * represented by a registered type alias.
	 *
	 * @param object $type Type metadata.
	 * @param object $item Normalized item.
	 *
	 * @return string Absolute routed URL.
	 */
	public function itemUrl(object $type, object $item): string
	{
		$parts = explode('.', (string) $type->key, 2);
		$option = $parts[0] ?? '';
		$view = $parts[1] ?? '';

		if (!preg_match('/^com_[a-z0-9_]+$/i', $option) || !preg_match('/^[a-z0-9_]+$/i', $view))
		{
			return '';
		}

		$link = 'index.php?option=' . rawurlencode($option) . '&view=' . rawurlencode($view) . '&id=' . rawurlencode((string) $item->id);

		if ((string) ($item->catid ?? '') !== '')
		{
			$link .= '&catid=' . rawurlencode((string) $item->catid);
		}

		return Route::link('site', $link, false, Route::TLS_IGNORE, true);
	}

	/**
	 * Converts a content-types row into safe table metadata.
	 *
	 * @param object $row Registry row.
	 *
	 * @return object|null Normalized type or null when unusable.
	 */
	private function normalizeType(object $row): ?object
	{
		$key = trim((string) $row->type_alias);

		if ($key === '' || str_ends_with(strtolower($key), '.category'))
		{
			return null;
		}

		$tableInfo = json_decode((string) $row->table, true);
		$mappings = json_decode((string) $row->field_mappings, true);
		$special = is_array($tableInfo) ? ($tableInfo['special'] ?? null) : null;
		$common = is_array($mappings) ? ($mappings['common'] ?? null) : null;

		if (!is_array($special) || !is_array($common))
		{
			return null;
		}

		$table = trim((string) ($special['dbtable'] ?? ''));
		$id = $this->column((string) ($special['key'] ?? ($common['core_content_item_id'] ?? '')));
		$title = $this->column((string) ($common['core_title'] ?? ''));
		$published = $this->column((string) ($common['core_publish_up'] ?? ($common['core_created_time'] ?? '')));

		if (!$this->table($table) || $id === null || $title === null || $published === null)
		{
			return null;
		}

		$label = Text::_((string) $row->type_title);

		return (object) [
			'key' => $key,
			'label' => $label,
			'table' => $table,
			'id' => $id,
			'title' => $title,
			'body' => $this->column((string) ($common['core_body'] ?? '')),
			'state' => $this->column((string) ($common['core_state'] ?? '')),
			'published' => $published,
			'publish_down' => $this->column((string) ($common['core_publish_down'] ?? '')),
			'created' => $this->column((string) ($common['core_created_time'] ?? '')),
			'catid' => $this->column((string) ($common['core_catid'] ?? '')),
			'alias' => $this->column((string) ($common['core_alias'] ?? '')),
		];
	}

	/**
	 * Queries one registered component table using validated identifiers only.
	 *
	 * @param object      $type   Normalized type metadata.
	 * @param string|null $cutoff UTC SQL lower bound.
	 * @param int         $limit  Per-type result cap.
	 * @param string|null $itemId Optional exact item ID.
	 *
	 * @return array<int,object> Normalized rows.
	 */
	private function queryType(object $type, ?string $cutoff, int $limit, ?string $itemId = null): array
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$publishedColumn = $this->db->quoteName((string) $type->published);
		$createdColumn = $type->created !== null ? $this->db->quoteName((string) $type->created) : null;
		$publishedExpression = $createdColumn !== null && $type->created !== $type->published
			? 'COALESCE(' . $publishedColumn . ', ' . $createdColumn . ')'
			: $publishedColumn;
		$select = [
			$this->db->quoteName((string) $type->id) . ' AS ' . $this->db->quoteName('id'),
			$this->db->quoteName((string) $type->title) . ' AS ' . $this->db->quoteName('title'),
			$publishedExpression . ' AS ' . $this->db->quoteName('published'),
		];

		foreach (['body', 'created', 'catid', 'alias'] as $optional)
		{
			$column = $type->{$optional} ?? null;
			$select[] = $column !== null
				? $this->db->quoteName((string) $column) . ' AS ' . $this->db->quoteName($optional)
				: 'NULL AS ' . $this->db->quoteName($optional);
		}

		$query = $this->db->getQuery(true)
			->select($select)
			->from($this->db->quoteName((string) $type->table))
			->order($publishedExpression . ' DESC');

		if ($type->state !== null)
		{
			$query->where($this->db->quoteName((string) $type->state) . ' = 1');
		}

		$query->where($publishedExpression . ' <= :now')
			->bind(':now', $now);

		if ($type->publish_down !== null)
		{
			$nowDown = $now;
			$query->where('(' . $this->db->quoteName((string) $type->publish_down) . ' IS NULL OR ' . $this->db->quoteName((string) $type->publish_down) . ' >= :nowDown)')
				->bind(':nowDown', $nowDown);
		}

		if ($cutoff !== null && $cutoff !== '')
		{
			$cutoffValue = $cutoff;
			$query->where($publishedExpression . ' > :cutoff')
				->bind(':cutoff', $cutoffValue);
		}

		if ($itemId !== null)
		{
			$itemValue = $itemId;
			$query->where('CAST(' . $this->db->quoteName((string) $type->id) . ' AS CHAR) = :itemId')
				->bind(':itemId', $itemValue);
		}

		$rows = $this->db->setQuery($query, 0, max(1, $limit))->loadObjectList();

		foreach ($rows as $row)
		{
			$row->source_key = (string) $type->key;
			$row->source_label = (string) $type->label;
			$row->id = (string) $row->id;
			$row->body = (string) ($row->body ?? '');
			$row->published = (string) ($row->published ?? ($row->created ?? ''));
			$row->url = $this->itemUrl($type, $row);
		}

		return $rows;
	}

	/** @return bool */
	private function table(string $value): bool
	{
		return preg_match('/^#__[A-Za-z0-9_]+$/', $value) === 1;
	}

	/** @return string|null */
	private function column(string $value): ?string
	{
		$value = trim($value);

		return preg_match('/^[A-Za-z0-9_]+$/', $value) === 1 ? $value : null;
	}
}
