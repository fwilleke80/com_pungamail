<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Newsletter editor model.
 */
final class NewsletterModel extends BaseDatabaseModel
{
	/** @return object|null */
	public function getItem(): ?object
	{
		$id = Factory::getApplication()->getInput()->getInt('id');

		return $id > 0 ? ServiceFactory::newsletters()->find($id) : null;
	}

	/** @return array<int,object> */
	public function getSelectedItems(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::newsletters()->getItems((int) $item->id) : [];
	}

	/** @return string|null */
	public function getContentCutoffStart(): ?string
	{
		$item = $this->getItem();

		return $item?->content_cutoff_start ?: ServiceFactory::newsletters()->getLastContentCutoff();
	}

	/** @return array<int,object> */
	public function getAvailableArticles(): array
	{
		return ServiceFactory::newsletters()->getAvailableArticles($this->getContentCutoffStart(), 150);
	}

	/** @return array<int,object> */
	public function getQueueRecipients(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::newsletters()->getQueueRecipients((int) $item->id) : [];
	}

	/** @return array<int,object> */
	public function getUserGroups(): array
	{
		return ServiceFactory::newsletters()->getUserGroups();
	}

	/** @return array<int,int> */
	public function getSelectedGroupIds(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::newsletters()->getGroupIds((int) $item->id) : [];
	}
}
