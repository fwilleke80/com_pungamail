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
		$id = max(0, (int) Factory::getApplication()->getInput()->getInt('id', 0));
		$this->item = $id > 0 ? ServiceFactory::newsletters()->find($id) : null;

		if ($this->item !== null && in_array((int) $this->item->status, [
			\Punga\Component\PungaMail\Administrator\Service\NewsletterRepository::STATUS_DRAFT,
			\Punga\Component\PungaMail\Administrator\Service\NewsletterRepository::STATUS_SCHEDULED,
		], true))
		{
			ServiceFactory::checkouts()->checkout('newsletter', $id, (int) Factory::getApplication()->getIdentity()->id);
			$this->item = ServiceFactory::newsletters()->find($id);
		}

		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			$item = $this->item ?? (object) [
				'id' => $id,
				'state' => 1,
				'status' => \Punga\Component\PungaMail\Administrator\Service\NewsletterRepository::STATUS_DRAFT,
				'include_subscribers' => 0,
				'scheduled_at' => null,
				'snapshot_html' => null,
			];
			$mapping = [
				'title' => 'title',
				'subject' => 'subject',
				'body' => 'body_markdown',
				'include_subscribers' => 'include_subscribers',
				'cutoff' => 'content_cutoff_start',
				'template_id' => 'template_id',
				'style_overrides' => 'style_overrides',
				'custom_css' => 'custom_css',
				'heading_mode' => 'heading_mode',
				'mail_heading' => 'mail_heading',
				'browser_view' => 'browser_view',
				'archive_visibility' => 'archive_visibility',
				'reply_to_mode' => 'reply_to_mode',
				'reply_to_email' => 'reply_to_email',
				'reply_to_name' => 'reply_to_name',
				'campaign_scope' => 'campaign_scope',
				'utm_source' => 'utm_source',
				'utm_medium' => 'utm_medium',
				'utm_campaign' => 'utm_campaign',
				'utm_id' => 'utm_id',
				'utm_content' => 'utm_content',
				'scheduled_at_input' => 'submitted_scheduled_at',
			];

			foreach ($mapping as $source => $target)
			{
				if (array_key_exists($source, $submitted))
				{
					$item->{$target} = $submitted[$source];
				}
			}

			$item->id = $id;
			$this->item = $item;
		}

		return $this->item;
	}

	/** @return array<int,object> */
	public function getSelectedItems(): array
	{
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return array_map(static fn (array $item): object => (object) $item, (array) ($submitted['items'] ?? []));
		}

		$item = $this->getItem();
		return $item && (int) ($item->id ?? 0) > 0 ? ServiceFactory::newsletters()->getItems((int) $item->id) : [];
	}

	/** @return string|null */
	public function getContentCutoffStart(): ?string
	{
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return $submitted['cutoff'] ?? null;
		}

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
		$types = $this->getContentTypes();
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return array_values(array_filter(
				array_map('strval', (array) ($submitted['sources'] ?? [])),
				static fn (string $key): bool => isset($types[$key])
			));
		}

		$item = $this->getItem();
		$keys = $item && (int) ($item->id ?? 0) > 0 ? ServiceFactory::newsletters()->getSourceKeys((int) $item->id) : ['com_content.article'];
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
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return ServiceFactory::styles()->decode($submitted['style_overrides'] ?? null);
		}

		$item = $this->getItem();
		return ServiceFactory::styles()->decode($item?->style_overrides ?? null);
	}

	/** @return array<int,object> */
	public function getQueueRecipients(): array
	{
		$item = $this->getItem();
		return $item && (int) ($item->id ?? 0) > 0 ? ServiceFactory::newsletters()->getQueueRecipients((int) $item->id) : [];
	}

	/** @return array<int,object> */
	public function getUserGroups(): array
	{
		return ServiceFactory::newsletters()->getUserGroups();
	}

	/** @return array<int,int> */
	public function getSelectedGroupIds(): array
	{
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return array_values(array_unique(array_map('intval', (array) ($submitted['groups'] ?? []))));
		}

		$item = $this->getItem();
		return $item && (int) ($item->id ?? 0) > 0 ? ServiceFactory::newsletters()->getGroupIds((int) $item->id) : [];
	}

	/** @return array<int,object> */
	public function getTopics(): array
	{
		return ServiceFactory::topics()->activeWithSelected($this->getSelectedTopicIds());
	}

	/** @return array<int,int> */
	public function getSelectedTopicIds(): array
	{
		$submitted = $this->getSubmittedData();

		if ($submitted !== [])
		{
			return array_values(array_unique(array_map('intval', (array) ($submitted['topics'] ?? []))));
		}

		$item = $this->getItem();

		return $item && (int) ($item->id ?? 0) > 0 ? ServiceFactory::newsletters()->getTopicIds((int) $item->id) : [];
	}

	/** @return array<string,int> */
	public function getStatistics(): array
	{
		$item = $this->getItem();

		return $item && (int) ($item->id ?? 0) > 0 ? ServiceFactory::statistics()->forNewsletter($item) : [];
	}

	/** @return array<string,mixed> */
	private function getSubmittedData(): array
	{
		$app = Factory::getApplication();
		$id = max(0, (int) $app->getInput()->getInt('id', 0));
		$data = (array) $app->getUserState('com_pungamail.edit.newsletter.data', []);

		return (int) ($data['id'] ?? -1) === $id ? $data : [];
	}
}
