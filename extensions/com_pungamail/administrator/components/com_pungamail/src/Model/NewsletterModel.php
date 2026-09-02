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
		$this->item = $id > 0 ? ServiceFactory::newsletters()->find($id) : null;

		if ($this->item !== null && in_array((int) $this->item->status, [
			\Punga\Component\PungaMail\Administrator\Service\NewsletterRepository::STATUS_DRAFT,
			\Punga\Component\PungaMail\Administrator\Service\NewsletterRepository::STATUS_SCHEDULED,
		], true))
		{
			ServiceFactory::checkouts()->checkout('newsletter', $id, (int) Factory::getApplication()->getIdentity()->id);
			$this->item = ServiceFactory::newsletters()->find($id);
		}

		return $this->item;
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

	/** @return array<string,object> */
	public function getContentTypes(): array
	{
		return ServiceFactory::contentTypes()->getTypes();
	}

	/** @return array<int,string> */
	public function getSelectedSourceKeys(): array
	{
		$item = $this->getItem();
		$types = $this->getContentTypes();
		$keys = $item ? ServiceFactory::newsletters()->getSourceKeys((int) $item->id) : ['com_content.article'];
		$keys = array_values(array_filter($keys, static fn (string $key): bool => isset($types[$key])));

		if ($keys === [] && $types !== [])
		{
			$keys = [array_key_first($types)];
		}

		return $keys;
	}

	/** @return array<int,object> */
	public function getAvailableContent(): array
	{
		$service = ServiceFactory::contentTypes();
		$items = $service->getItems($this->getSelectedSourceKeys(), $this->getContentCutoffStart(), 500);
		$known = [];

		foreach ($items as $item)
		{
			$known[(string) $item->source_key . "\0" . (string) $item->id] = true;
		}

		foreach ($this->getSelectedItems() as $selection)
		{
			$key = (string) $selection->source_key . "\0" . (string) $selection->source_item_id;
			if (isset($known[$key]))
			{
				continue;
			}

			$current = $service->findItem((string) $selection->source_key, (string) $selection->source_item_id);
			if ($current !== null)
			{
				$items[] = $current;
			}
		}

		usort($items, static fn (object $a, object $b): int => strcmp((string) $b->published, (string) $a->published));
		return $items;
	}

	/** @return array<int,object> */
	public function getTemplates(): array
	{
		return ServiceFactory::templates()->active();
	}

	/** @return array<string,string> */
	public function getStyleOverrides(): array
	{
		$item = $this->getItem();
		return ServiceFactory::styles()->decode($item?->style_overrides ?? null);
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

	/** @return array<int,object> */
	public function getTopics(): array
	{
		return ServiceFactory::topics()->active();
	}

	/** @return array<int,int> */
	public function getSelectedTopicIds(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::newsletters()->getTopicIds((int) $item->id) : [];
	}

	/** @return array<string,int> */
	public function getStatistics(): array
	{
		$item = $this->getItem();

		return $item ? ServiceFactory::statistics()->forNewsletter($item) : [];
	}
}
