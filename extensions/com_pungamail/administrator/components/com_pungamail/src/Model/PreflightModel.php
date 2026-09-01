<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Preflight model showing the exact current recipient set and render preview.
 */
final class PreflightModel extends BaseDatabaseModel
{
	/**
	 * Returns all preflight data.
	 *
	 * @return array<string,mixed>
	 */
	public function getData(): array
	{
		$id = Factory::getApplication()->getInput()->getInt('id');
		$repo = ServiceFactory::newsletters();
		$newsletter = $repo->find($id);

		if ($newsletter === null)
		{
			throw new \RuntimeException('Newsletter not found.');
		}

		$items = $repo->getItems($id);
		$groups = $repo->getGroupIds($id);
		$recipients = ServiceFactory::recipients()->resolve($newsletter, $groups);
		$rendered = ServiceFactory::renderer()->render($newsletter, $items);
		$params = ComponentHelper::getParams('com_pungamail');
		$sourceCounts = [];

		foreach ($recipients as $recipient)
		{
			$source = (string) $recipient['source'];
			$sourceCounts[$source] = ($sourceCounts[$source] ?? 0) + 1;
		}

		return [
			'newsletter' => $newsletter,
			'items' => $items,
			'groups' => $groups,
			'recipients' => $recipients,
			'rendered' => $rendered,
			'source_counts' => $sourceCounts,
			'from_email' => (string) $params->get('from_email', ''),
			'from_name' => (string) $params->get('from_name', ''),
		];
	}
}
