<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRepository;

/** Loads a public immutable browser snapshot by its random public key. */
final class BrowserModel extends BaseDatabaseModel
{
	/** @return object|null */
	public function getNewsletter(): ?object
	{
		$input = Factory::getApplication()->getInput();
		$id = $input->getInt('id');
		$key = trim($input->getString('key'));

		if ($id <= 0 || strlen($key) !== 32)
		{
			return null;
		}

		$statusSending = NewsletterRepository::STATUS_SENDING;
		$statusSent = NewsletterRepository::STATUS_SENT;
		$statusFailures = NewsletterRepository::STATUS_SENT_WITH_FAILURES;
		$statusCancelled = NewsletterRepository::STATUS_CANCELLED;
		$query = $this->getDatabase()->getQuery(true)
			->select(['id', 'title', 'snapshot_subject', 'snapshot_html', 'snapshot_browser_token', 'sent_count', 'status', 'sent_at'])
			->from($this->getDatabase()->quoteName('#__pungamail_newsletters'))
			->where($this->getDatabase()->quoteName('id') . ' = :id')
			->where($this->getDatabase()->quoteName('snapshot_browser_enabled') . ' = 1')
			->where($this->getDatabase()->quoteName('snapshot_html') . ' IS NOT NULL')
			->whereIn($this->getDatabase()->quoteName('status'), [$statusSending, $statusSent, $statusFailures, $statusCancelled])
			->bind(':id', $id, ParameterType::INTEGER);
		$row = $this->getDatabase()->setQuery($query)->loadObject();

		if ($row === null || !hash_equals((string) $row->snapshot_browser_token, $key))
		{
			return null;
		}

		if ((int) $row->status === NewsletterRepository::STATUS_CANCELLED && (int) $row->sent_count <= 0)
		{
			return null;
		}

		return $row;
	}
}
