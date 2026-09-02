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
	public function save(int $id, string $title, string $subject, string $body, ?string $styleOverrides, string $customCss, int $userId, array $options = []): int
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$headingMode = in_array((string) ($options['heading_mode'] ?? 'inherit'), ['inherit', 'custom', 'site', 'none'], true) ? (string) ($options['heading_mode'] ?? 'inherit') : 'inherit';
		$mailHeading = trim((string) ($options['mail_heading'] ?? ''));
		$browserView = in_array((int) ($options['browser_view'] ?? -1), [-1, 0, 1], true) ? (int) ($options['browser_view'] ?? -1) : -1;
		$replyToMode = in_array((string) ($options['reply_to_mode'] ?? 'inherit'), ['inherit', 'custom', 'none'], true) ? (string) ($options['reply_to_mode'] ?? 'inherit') : 'inherit';
		$replyToEmail = trim((string) ($options['reply_to_email'] ?? ''));
		$replyToName = trim((string) ($options['reply_to_name'] ?? ''));

		if ($id <= 0)
		{
			$row = (object) [
				'title' => $title,
				'subject' => $subject,
				'body_markdown' => $body,
				'state' => 1,
				'style_overrides' => $styleOverrides,
				'custom_css' => $customCss !== '' ? $customCss : null,
				'heading_mode' => $headingMode,
				'mail_heading' => $mailHeading !== '' ? $mailHeading : null,
				'browser_view' => $browserView,
				'reply_to_mode' => $replyToMode,
				'reply_to_email' => $replyToEmail !== '' ? $replyToEmail : null,
				'reply_to_name' => $replyToName !== '' ? $replyToName : null,
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
			->set($this->db->quoteName('heading_mode') . ' = :headingMode')
			->set($this->db->quoteName('mail_heading') . ($mailHeading === '' ? ' = NULL' : ' = :mailHeading'))
			->set($this->db->quoteName('browser_view') . ' = :browserView')
			->set($this->db->quoteName('reply_to_mode') . ' = :replyToMode')
			->set($this->db->quoteName('reply_to_email') . ($replyToEmail === '' ? ' = NULL' : ' = :replyToEmail'))
			->set($this->db->quoteName('reply_to_name') . ($replyToName === '' ? ' = NULL' : ' = :replyToName'))
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':title', $title)
			->bind(':subject', $subject)
			->bind(':body', $body)
			->bind(':headingMode', $headingMode)
			->bind(':browserView', $browserView, ParameterType::INTEGER)
			->bind(':replyToMode', $replyToMode)
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

		if ($mailHeading !== '')
		{
			$query->bind(':mailHeading', $mailHeading);
		}

		if ($replyToEmail !== '')
		{
			$query->bind(':replyToEmail', $replyToEmail);
		}

		if ($replyToName !== '')
		{
			$query->bind(':replyToName', $replyToName);
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
