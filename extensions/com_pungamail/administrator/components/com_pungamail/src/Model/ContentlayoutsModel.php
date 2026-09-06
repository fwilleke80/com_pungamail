<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ContentLayoutRepository;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Central selected-content layout list model. */
final class ContentlayoutsModel extends BaseDatabaseModel
{
	/** @return array<int,object> */
	public function getItems(): array
	{
		$layouts = ServiceFactory::contentLayouts()->all();
		$default = $layouts[ContentLayoutRepository::DEFAULT_KEY] ?? null;
		$items = [
			(object) [
				'source_key' => ContentLayoutRepository::DEFAULT_KEY,
				'label' => 'Default',
				'table' => '',
				'custom' => true,
				'modified' => (string) ($default->modified ?? ''),
			],
		];

		foreach (ServiceFactory::contentTypes()->getTypes() as $key => $type)
		{
			$layout = $layouts[$key] ?? null;
			$items[] = (object) [
				'source_key' => $key,
				'label' => (string) $type->label,
				'table' => (string) $type->table,
				'custom' => $layout !== null,
				'modified' => (string) ($layout->modified ?? ''),
			];
		}

		return $items;
	}

	/** @return array{newsletters:int,templates:int} */
	public function getLegacyOverrideCounts(): array
	{
		return ServiceFactory::contentLayouts()->legacyOverrideCounts();
	}
}
