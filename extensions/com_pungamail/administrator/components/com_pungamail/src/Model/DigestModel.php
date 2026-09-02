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

/** Digest automation editor model. */
final class DigestModel extends BaseDatabaseModel
{
	private bool $itemLoaded = false;
	private ?object $item = null;

	/** @return object|null */
	public function getItem(): ?object
	{
		if ($this->itemLoaded)
		{
			return $this->item;
		}

		$this->itemLoaded = true;
		$id = Factory::getApplication()->getInput()->getInt('id');
		$this->item = $id > 0 ? ServiceFactory::digests()->find($id) : null;

		if ($this->item !== null)
		{
			ServiceFactory::checkouts()->checkout('digest', $id, (int) Factory::getApplication()->getIdentity()->id);
			$this->item = ServiceFactory::digests()->find($id);
		}

		return $this->item;
	}

	/** @return array<int,object> */
	public function getTemplates(): array
	{
		return ServiceFactory::templates()->active();
	}

	/** @return array<string,object> */
	public function getContentTypes(): array
	{
		return ServiceFactory::contentTypes()->getTypes();
	}

	/** @return array<int,object> */
	public function getTopics(): array
	{
		return ServiceFactory::topics()->active();
	}

	/** @return array<int,object> */
	public function getGroups(): array
	{
		return ServiceFactory::newsletters()->getUserGroups();
	}

	/** @return array<int,string> */
	public function getSourceKeys(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::digests()->getSourceKeys((int) $item->id) : [];
	}

	/** @return array<int,int> */
	public function getTopicIds(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::digests()->getTopicIds((int) $item->id) : [];
	}

	/** @return array<int,int> */
	public function getGroupIds(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::digests()->getGroupIds((int) $item->id) : [];
	}

	/** @return array<string,array<int,int>> */
	public function getCategories(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::digests()->getCategories((int) $item->id) : [];
	}

	/** @return array<int,object> */
	public function getRuns(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::digests()->getRuns((int) $item->id) : [];
	}
}
