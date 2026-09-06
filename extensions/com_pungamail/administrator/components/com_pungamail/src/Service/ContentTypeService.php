<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
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

	/** @var array<string,int|null> */
	private array $categoryAccess = [];

	/** @var array<string,array<int,array{name:string,type:string,date_like:bool}>> */
	private array $tableColumns = [];

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
	 * Returns safe database columns that can be used as layout placeholders.
	 *
	 * Punga Mail discovers these from the registered content type's backing
	 * table; the originating extension does not need Punga Mail integration.
	 *
	 * @param string $sourceKey Registered content type alias.
	 *
	 * @return array<int,array{name:string,type:string,date_like:bool}>
	 */
	public function getPlaceholderColumns(string $sourceKey): array
	{
		$type = $this->getTypes()[$sourceKey] ?? null;

		return $type !== null ? $this->columnsForType($type) : [];
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
	 * Keeps only items that every resolved recipient may view on the website.
	 * External recipients use Joomla's guest view levels. Standard Joomla
	 * category access is checked in addition to the registered item access field.
	 *
	 * @param array<int,object>              $items      Normalized content items.
	 * @param array<int,array<string,mixed>> $recipients Resolved recipients.
	 *
	 * @return array{items:array<int,object>,violations:array<int,array<string,mixed>>}
	 */
	public function filterForRecipients(array $items, array $recipients): array
	{
		$userIds = $recipients === []
			? [0]
			: array_values(array_unique(array_map(static fn (array $recipient): int => (int) ($recipient['user_id'] ?? 0), $recipients)));
		$levelsByUser = [];

		foreach ($userIds as $userId)
		{
			$levelsByUser[$userId] = array_flip(array_map('intval', Access::getAuthorisedViewLevels($userId)));
		}

		$allowed = [];
		$violations = [];

		foreach ($items as $item)
		{
			if (!(bool) ($item->access_mapped ?? false))
			{
				$violations[] = [
					'source_key' => (string) ($item->source_key ?? ''),
					'source_item_id' => (string) ($item->id ?? ''),
					'title' => (string) ($item->title ?? ''),
					'blocked_user_ids' => $userIds,
					'reason' => 'access_metadata_unavailable',
				];
				continue;
			}

			$requiredLevels = [];
			$itemAccess = isset($item->access) && $item->access !== null ? (int) $item->access : null;

			if ($itemAccess !== null && $itemAccess > 0)
			{
				$requiredLevels[] = $itemAccess;
			}

			$categoryAccess = $this->getCategoryAccess((string) ($item->source_key ?? ''), (int) ($item->catid ?? 0));

			if ($categoryAccess === -1)
			{
				$violations[] = [
					'source_key' => (string) ($item->source_key ?? ''),
					'source_item_id' => (string) ($item->id ?? ''),
					'title' => (string) ($item->title ?? ''),
					'blocked_user_ids' => $userIds,
				];
				continue;
			}

			if ($categoryAccess !== null && $categoryAccess > 0)
			{
				$requiredLevels[] = $categoryAccess;
			}

			$blockedUsers = [];

			foreach ($levelsByUser as $userId => $levels)
			{
				foreach ($requiredLevels as $requiredLevel)
				{
					if (!isset($levels[$requiredLevel]))
					{
						$blockedUsers[] = $userId;
						break;
					}
				}
			}

			if ($blockedUsers === [])
			{
				$allowed[] = $item;
				continue;
			}

			$violations[] = [
				'source_key' => (string) ($item->source_key ?? ''),
				'source_item_id' => (string) ($item->id ?? ''),
				'title' => (string) ($item->title ?? ''),
				'blocked_user_ids' => $blockedUsers,
			];
		}

		return ['items' => $allowed, 'violations' => $violations];
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

		$label = $this->translatedTypeLabel((string) $row->type_title, $key);

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
			'access' => $this->column((string) ($common['core_access'] ?? '')),
		];
	}


	/**
	 * Resolves a registered content type to the current administrator language.
	 * Joomla's content-type registry commonly stores English type titles, so we
	 * first try the conventional component language key derived from type_alias.
	 * Third-party types that do not provide such a key safely fall back to the
	 * registry title.
	 *
	 * @param string $typeTitle Registry type title.
	 * @param string $typeAlias Registered type alias.
	 *
	 * @return string Translated display label.
	 */
	private function translatedTypeLabel(string $typeTitle, string $typeAlias): string
	{
		$parts = explode('.', $typeAlias, 2);
		$component = $parts[0] ?? '';

		if (preg_match('/^com_[a-z0-9_]+$/i', $component) === 1)
		{
			$language = Factory::getApplication()->getLanguage();
			$language->load($component, JPATH_ADMINISTRATOR);
			$language->load($component, JPATH_SITE);
		}

		$key = strtoupper(str_replace('.', '_', $typeAlias));
		$translated = Text::_($key);

		if ($translated !== $key)
		{
			return $translated;
		}

		$translatedTitle = Text::_($typeTitle);

		return $translatedTitle !== $typeTitle ? $translatedTitle : $typeTitle;
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

		foreach (['body', 'created', 'catid', 'alias', 'access'] as $optional)
		{
			$column = $type->{$optional} ?? null;
			$select[] = $column !== null
				? $this->db->quoteName((string) $column) . ' AS ' . $this->db->quoteName($optional)
				: 'NULL AS ' . $this->db->quoteName($optional);
		}

		$rawAliases = [];
		foreach ($this->columnsForType($type) as $index => $columnInfo)
		{
			$alias = 'pm_raw_' . $index;
			$rawAliases[$alias] = $columnInfo['name'];
			$select[] = $this->db->quoteName($columnInfo['name']) . ' AS ' . $this->db->quoteName($alias);
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
			$row->raw_fields = [];

			foreach ($rawAliases as $alias => $columnName)
			{
				$row->raw_fields[$columnName] = $row->{$alias} ?? null;
				unset($row->{$alias});
			}

			$row->source_key = (string) $type->key;
			$row->source_label = (string) $type->label;
			$row->id = (string) $row->id;
			$row->body = (string) ($row->body ?? '');
			$row->access = $row->access !== null ? (int) $row->access : null;
			$row->access_mapped = $type->access !== null;
			$row->published = (string) ($row->published ?? ($row->created ?? ''));
			$row->url = $this->itemUrl($type, $row);
		}

		return $rows;
	}

	/** @return int|null */
	private function getCategoryAccess(string $sourceKey, int $categoryId): ?int
	{
		if ($categoryId <= 0)
		{
			return null;
		}

		$component = explode('.', $sourceKey, 2)[0] ?? '';

		if (!preg_match('/^com_[a-z0-9_]+$/i', $component))
		{
			return null;
		}

		$cacheKey = $component . ':' . $categoryId;

		if (array_key_exists($cacheKey, $this->categoryAccess))
		{
			return $this->categoryAccess[$cacheKey];
		}

		$query = $this->db->getQuery(true)
			->select([$this->db->quoteName('access'), $this->db->quoteName('published')])
			->from($this->db->quoteName('#__categories'))
			->where($this->db->quoteName('id') . ' = :categoryId')
			->where($this->db->quoteName('extension') . ' = :extension')
			->bind(':categoryId', $categoryId, \Joomla\Database\ParameterType::INTEGER)
			->bind(':extension', $component);
		$row = $this->db->setQuery($query)->loadObject();
		$this->categoryAccess[$cacheKey] = $row === null || (int) $row->published !== 1 ? -1 : (int) $row->access;

		return $this->categoryAccess[$cacheKey];
	}

	/**
	 * Introspects one registered source table and returns safe placeholder fields.
	 *
	 * @param object $type Normalized content type.
	 *
	 * @return array<int,array{name:string,type:string,date_like:bool}>
	 */
	private function columnsForType(object $type): array
	{
		$key = (string) $type->key;

		if (isset($this->tableColumns[$key]))
		{
			return $this->tableColumns[$key];
		}

		$table = $this->db->replacePrefix((string) $type->table);
		$columns = $this->db->getTableColumns($table, false);
		$result = [];

		foreach ($columns as $columnName => $metadata)
		{
			$name = is_string($columnName) ? $columnName : (string) ($metadata->Field ?? $metadata->field ?? '');

			if ($this->column($name) === null || $this->sensitiveColumn($name))
			{
				continue;
			}

			$typeName = '';

			if (is_string($metadata))
			{
				$typeName = $metadata;
			}
			elseif (is_object($metadata))
			{
				$typeName = (string) ($metadata->Type ?? $metadata->type ?? '');
			}
			elseif (is_array($metadata))
			{
				$typeName = (string) ($metadata['Type'] ?? $metadata['type'] ?? '');
			}

			$dateLike = preg_match('/(?:date|time|year)/i', $typeName) === 1
				|| preg_match('/(?:date|time|created|modified|publish|start|end|begin|finish|from|until|at)$/i', $name) === 1;
			$result[] = ['name' => $name, 'type' => $typeName, 'date_like' => $dateLike];
		}

		usort($result, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));
		$this->tableColumns[$key] = $result;

		return $result;
	}

	/** @return bool */
	private function sensitiveColumn(string $name): bool
	{
		return preg_match('/(?:password|passwd|secret|token|api[_-]?key|private[_-]?key|otp|totp|credential)/i', $name) === 1;
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
