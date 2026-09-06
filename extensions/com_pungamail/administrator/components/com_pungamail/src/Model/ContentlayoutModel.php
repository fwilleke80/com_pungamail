<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ContentLayoutRepository;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Central selected-content layout editor model. */
final class ContentlayoutModel extends BaseDatabaseModel
{
	/** @return object */
	public function getItem(): object
	{
		$sourceKey = trim(Factory::getApplication()->getInput()->getString('source_key', ContentLayoutRepository::DEFAULT_KEY));
		$repository = ServiceFactory::contentLayouts();

		if ($sourceKey === ContentLayoutRepository::DEFAULT_KEY)
		{
			$row = $repository->find($sourceKey);

			return (object) [
				'source_key' => $sourceKey,
				'label' => Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_DEFAULT'),
				'table' => '',
				'custom' => true,
				'layout_markdown' => (string) ($row->layout_markdown ?? $repository->defaultLayout()),
				'columns' => [],
			];
		}

		$type = ServiceFactory::contentTypes()->getTypes()[$sourceKey] ?? null;

		if ($type === null)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_UNKNOWN_TYPE'), 404);
		}

		$row = $repository->find($sourceKey);

		return (object) [
			'source_key' => $sourceKey,
			'label' => (string) $type->label,
			'table' => (string) $type->table,
			'custom' => $row !== null,
			'layout_markdown' => (string) ($row->layout_markdown ?? $repository->defaultLayout()),
			'columns' => ServiceFactory::contentTypes()->getPlaceholderColumns($sourceKey),
		];
	}
}
