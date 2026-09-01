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
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Render-only newsletter preview model.
 */
final class PreviewModel extends BaseDatabaseModel
{
	/**
	 * @return array{newsletter:object,rendered:array<string,mixed>}
	 */
	public function getData(): array
	{
		$id = Factory::getApplication()->getInput()->getInt('id');
		$repo = ServiceFactory::newsletters();
		$newsletter = $repo->find($id);

		if ($newsletter === null)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_NEWSLETTER_NOT_FOUND'));
		}

		return [
			'newsletter' => $newsletter,
			'rendered' => ServiceFactory::renderer()->render($newsletter, $repo->getItems($id)),
		];
	}
}
