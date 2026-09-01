<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/** Public status-message model. */
final class MessageModel extends BaseDatabaseModel
{
	/** @return string */
	public function getType(): string
	{
		return Factory::getApplication()->getInput()->getCmd('type', 'requested');
	}
}
