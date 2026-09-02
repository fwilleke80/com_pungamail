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
use Punga\Component\PungaMail\Administrator\Service\RecipientName;
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
		$data = ServiceFactory::preflight()->analyze($id);
		$renderer = ServiceFactory::renderer();
		$identity = Factory::getApplication()->getIdentity();
		$personalized = $renderer->personalize(
			$data['rendered']['subject'],
			$data['rendered']['html'],
			$data['rendered']['text'],
			RecipientName::resolve((string) $identity->name, (string) $identity->email)
		);
		$data['rendered'] = $personalized;
		$sourceCounts = [];

		foreach ($data['recipients'] as $recipient)
		{
			$source = (string) $recipient['source'];
			$sourceCounts[$source] = ($sourceCounts[$source] ?? 0) + 1;
		}

		$data['source_counts'] = $sourceCounts;

		return $data;
	}
}
