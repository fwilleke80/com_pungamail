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

/** Subscriber CSV import/export view model. */
final class ImportModel extends BaseDatabaseModel
{
	/** @return array<string,mixed>|null */
	public function getPreview(): ?array
	{
		$contents = (string) Factory::getApplication()->getSession()->get('pungamail.csv.contents', '');

		return $contents !== '' ? ServiceFactory::csv()->preview($contents) : null;
	}

	/** @return array<int,object> */
	public function getTopics(): array
	{
		return ServiceFactory::topics()->active();
	}
}
