<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Date\Date;
use Joomla\Database\DatabaseInterface;

/** Stores central Markdown layouts used for selected Joomla content items. */
final class ContentLayoutRepository
{
	public const DEFAULT_KEY = '__default__';
	public const DEFAULT_LAYOUT = "### {title_link}\n\n{excerpt}\n\n{read_more}";

	/** @var array<string,object|null> */
	private array $cache = [];

	/** @param DatabaseInterface $db Database connection. */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/** @return object|null */
	public function find(string $sourceKey): ?object
	{
		if (array_key_exists($sourceKey, $this->cache))
		{
			return $this->cache[$sourceKey];
		}

		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_content_layouts'))
			->where($this->db->quoteName('source_key') . ' = :sourceKey')
			->bind(':sourceKey', $sourceKey);

		$row = $this->db->setQuery($query)->loadObject() ?: null;
		$this->cache[$sourceKey] = $row;

		return $row;
	}

	/** @return array<string,object> */
	public function all(): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_content_layouts'));
		$rows = $this->db->setQuery($query)->loadObjectList();
		$result = [];

		foreach ($rows as $row)
		{
			$result[(string) $row->source_key] = $row;
		}

		return $result;
	}

	/** @return string */
	public function defaultLayout(): string
	{
		$row = $this->find(self::DEFAULT_KEY);
		$value = trim((string) ($row->layout_markdown ?? ''));

		return $value !== '' ? $value : self::DEFAULT_LAYOUT;
	}

	/** @return string */
	public function layoutFor(string $sourceKey): string
	{
		$row = $this->find($sourceKey);
		$value = trim((string) ($row->layout_markdown ?? ''));

		return $value !== '' ? $value : $this->defaultLayout();
	}

	/** @return bool */
	public function hasCustom(string $sourceKey): bool
	{
		return $sourceKey !== self::DEFAULT_KEY && $this->find($sourceKey) !== null;
	}

	/** @return void */
	public function save(string $sourceKey, string $layoutMarkdown): void
	{
		$layoutMarkdown = trim($layoutMarkdown);

		if ($layoutMarkdown === '')
		{
			throw new \InvalidArgumentException('Content layout must not be empty.');
		}

		$now = (new Date('now', 'UTC'))->toSql();
		$existing = $this->find($sourceKey);

		if ($existing === null)
		{
			$row = (object) [
				'source_key' => $sourceKey,
				'layout_markdown' => $layoutMarkdown,
				'created' => $now,
				'modified' => $now,
			];
			$this->db->insertObject('#__pungamail_content_layouts', $row);
			$this->cache[$sourceKey] = $row;

			return;
		}

		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_content_layouts'))
			->set($this->db->quoteName('layout_markdown') . ' = :layout')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('source_key') . ' = :sourceKey')
			->bind(':layout', $layoutMarkdown)
			->bind(':modified', $now)
			->bind(':sourceKey', $sourceKey);
		$this->db->setQuery($query)->execute();
		$this->cache[$sourceKey] = (object) [
			'source_key' => $sourceKey,
			'layout_markdown' => $layoutMarkdown,
			'modified' => $now,
		];
	}

	/** @return void */
	public function reset(string $sourceKey): void
	{
		if ($sourceKey === self::DEFAULT_KEY)
		{
			$this->save(self::DEFAULT_KEY, self::DEFAULT_LAYOUT);
			return;
		}

		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_content_layouts'))
			->where($this->db->quoteName('source_key') . ' = :sourceKey')
			->bind(':sourceKey', $sourceKey);
		$this->db->setQuery($query)->execute();
		$this->cache[$sourceKey] = null;
	}

	/** @return array{newsletters:int,templates:int} */
	public function legacyOverrideCounts(): array
	{
		$result = ['newsletters' => 0, 'templates' => 0];

		foreach (['newsletters', 'templates'] as $table)
		{
			$query = $this->db->getQuery(true)
				->select('COUNT(*)')
				->from($this->db->quoteName('#__pungamail_' . $table))
				->where($this->db->quoteName('new_content_item_template') . ' IS NOT NULL')
				->where('TRIM(' . $this->db->quoteName('new_content_item_template') . ") <> ''");
			$result[$table] = (int) $this->db->setQuery($query)->loadResult();
		}

		return $result;
	}
}
