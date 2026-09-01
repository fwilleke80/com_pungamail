<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Newsletter list model.
 */
final class NewslettersModel extends BaseDatabaseModel
{
	/**
	 * Returns newsletters with queue counts.
	 *
	 * @return array<int,object> Newsletter rows.
	 */
	public function getItems(): array
	{
		return ServiceFactory::newsletters()->all();
	}
}
