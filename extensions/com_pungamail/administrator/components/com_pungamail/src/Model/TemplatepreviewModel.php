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
use Punga\Component\PungaMail\Administrator\Service\RecipientName;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Template preview model. */
final class TemplatepreviewModel extends BaseDatabaseModel
{
	/** @return array<string,mixed> */
	public function getData(): array
	{
		$id = Factory::getApplication()->getInput()->getInt('id');
		$template = ServiceFactory::templates()->find($id);

		if ($template === null)
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_TEMPLATE_NOT_FOUND'), 404);
		}

		$renderer = ServiceFactory::renderer();
		$rendered = $renderer->renderTemplate($template);
		$identity = Factory::getApplication()->getIdentity();
		$personalized = $renderer->personalize(
			$rendered['subject'],
			$rendered['html'],
			$rendered['text'],
			RecipientName::resolve((string) $identity->name, (string) $identity->email)
		);
		$rendered['subject'] = $personalized['subject'];
		$rendered['html'] = $personalized['html'];
		$rendered['text'] = $personalized['text'];

		return ['template' => $template, 'rendered' => $rendered];
	}
}
