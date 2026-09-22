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
		$id = max(0, (int) Factory::getApplication()->getInput()->getInt('id', 0));
		$this->item = $id > 0 ? ServiceFactory::digests()->find($id) : null;

		if ($this->item !== null)
		{
			ServiceFactory::checkouts()->checkout('digest', $id, (int) Factory::getApplication()->getIdentity()->id);
			$this->item = ServiceFactory::digests()->find($id);
		}

		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			$item = $this->item ?? (object) [];

			foreach ([
				'title', 'state', 'template_id', 'subject_pattern', 'recurrence_value', 'recurrence_unit', 'recurrence_minutes', 'next_run_at',
				'cutoff_mode', 'rolling_hours', 'first_run_cutoff_mode', 'first_run_lookback_hours', 'include_subscribers', 'generation_mode', 'empty_action', 'content_order', 'max_items', 'minimum_items', 'confirm_auto_send',
				'campaign_scope', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_content',
			] as $key)
			{
				if (array_key_exists($key, $submitted))
				{
					$item->{$key} = $submitted[$key];
				}
			}

			$item->id = $id;
			$this->item = $item;
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
		return ServiceFactory::topics()->activeWithSelected($this->getTopicIds());
	}

	/** @return array<int,object> */
	public function getGroups(): array
	{
		return ServiceFactory::newsletters()->getUserGroups();
	}

	/** @return array<int,string> */
	public function getSourceKeys(): array
	{
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return array_values(array_filter(array_map('strval', (array) ($submitted['source_keys'] ?? []))));
		}

		$item = $this->getItem();

		return $item && (int) $item->id > 0 ? ServiceFactory::digests()->getSourceKeys((int) $item->id) : [];
	}

	/** @return array<int,int> */
	public function getTopicIds(): array
	{
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return array_values(array_unique(array_filter(array_map('intval', (array) ($submitted['topic_ids'] ?? [])))));
		}

		$item = $this->getItem();

		return $item && (int) $item->id > 0 ? ServiceFactory::digests()->getTopicIds((int) $item->id) : [];
	}

	/** @return array<int,int> */
	public function getGroupIds(): array
	{
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return array_values(array_unique(array_filter(array_map('intval', (array) ($submitted['group_ids'] ?? [])))));
		}

		$item = $this->getItem();

		return $item && (int) $item->id > 0 ? ServiceFactory::digests()->getGroupIds((int) $item->id) : [];
	}

	/** @return array<string,array<int,int>> */
	public function getCategories(): array
	{
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return (array) ($submitted['categories'] ?? []);
		}

		$item = $this->getItem();

		return $item && (int) $item->id > 0 ? ServiceFactory::digests()->getCategories((int) $item->id) : [];
	}

	/** @return array<string,array<int,array{field:string,operator:string,value:mixed}>> */
	public function getFilters(): array
	{
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return \Punga\Component\PungaMail\Administrator\Service\DigestContentFilter::normalize((array) ($submitted['filters'] ?? []));
		}

		$item = $this->getItem();

		return $item && (int) $item->id > 0 ? ServiceFactory::digests()->getFilters((int) $item->id) : [];
	}

	/** @return array<string,array<int,array{name:string,label:string,kind:string,date_like:bool,options:array<int,array{value:string,label:string}>}>> */
	public function getFilterFields(): array
	{
		$result = [];
		$service = ServiceFactory::contentTypes();

		foreach ($this->getContentTypes() as $key => $type)
		{
			$result[(string) $key] = $service->getFilterFields((string) $key);
		}

		return $result;
	}

	/** @return array<int,object> */
	public function getRuns(): array
	{
		$item = $this->getItem();

		return $item && (int) $item->id > 0 ? ServiceFactory::digests()->getRuns((int) $item->id) : [];
	}

	/** @return array<string,mixed> */
	private function getSubmittedData(): array
	{
		$app = Factory::getApplication();
		$id = max(0, (int) $app->getInput()->getInt('id', 0));
		$data = (array) $app->getUserState('com_pungamail.edit.digest.data', []);

		return (int) ($data['id'] ?? -1) === $id ? $data : [];
	}
}
