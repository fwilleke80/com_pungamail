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
use Joomla\Database\ParameterType;

/**
 * Persistence service for reusable newsletter templates.
 */
final class TemplateRepository
{
	/** @param DatabaseInterface $db Database connection. */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/** @return object|null */
	public function find(int $id): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_templates'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/** @return array<int,object> */
	public function active(): array
	{
		$state = 1;
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_templates'))
			->where($this->db->quoteName('state') . ' = :state')
			->order($this->db->quoteName('title') . ' ASC')
			->bind(':state', $state, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObjectList();
	}

	/**
	 * Saves a template.
	 *
	 * @return int Template ID.
	 */
	public function save(int $id, string $title, string $subject, string $body, ?string $styleOverrides, string $customCss, int $userId): int
	{
		$now = (new Date('now', 'UTC'))->toSql();

		if ($id <= 0)
		{
			$row = (object) [
				'title' => $title,
				'subject' => $subject,
				'body_markdown' => $body,
				'state' => 1,
				'style_overrides' => $styleOverrides,
				'custom_css' => $customCss !== '' ? $customCss : null,
				'created' => $now,
				'modified' => $now,
				'created_by' => $userId,
			];
			$this->db->insertObject('#__pungamail_templates', $row, 'id');

			return (int) $row->id;
		}

		$cssValue = $customCss !== '' ? $customCss : null;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_templates'))
			->set($this->db->quoteName('title') . ' = :title')
			->set($this->db->quoteName('subject') . ' = :subject')
			->set($this->db->quoteName('body_markdown') . ' = :body')
			->set($this->db->quoteName('style_overrides') . ($styleOverrides === null ? ' = NULL' : ' = :styleOverrides'))
			->set($this->db->quoteName('custom_css') . ($cssValue === null ? ' = NULL' : ' = :customCss'))
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':title', $title)
			->bind(':subject', $subject)
			->bind(':body', $body)
			->bind(':modified', $now)
			->bind(':id', $id, ParameterType::INTEGER);

		if ($styleOverrides !== null)
		{
			$query->bind(':styleOverrides', $styleOverrides);
		}

		if ($cssValue !== null)
		{
			$query->bind(':customCss', $cssValue);
		}

		$this->db->setQuery($query)->execute();

		return $id;
	}

	/** @return void */
	public function setState(array $ids, int $state): void
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return;
		}

		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_templates'))
			->set($this->db->quoteName('state') . ' = :state')
			->whereIn($this->db->quoteName('id'), $ids)
			->bind(':state', $state, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}

	/** @return int */
	public function deleteTrashed(array $ids): int
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if ($ids === [])
		{
			return 0;
		}

		$trashed = -2;
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_templates'))
			->whereIn($this->db->quoteName('id'), $ids)
			->where($this->db->quoteName('state') . ' = :state')
			->bind(':state', $trashed, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();

		return $this->db->getAffectedRows();
	}
}
