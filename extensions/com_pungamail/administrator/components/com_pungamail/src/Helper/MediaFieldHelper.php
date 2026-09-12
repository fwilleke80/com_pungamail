<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Helper
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormFactoryInterface;

/**
 * Renders small Joomla Media Manager fields used by the hand-built editors.
 */
final class MediaFieldHelper
{
	/**
	 * Renders an image-only Joomla Media Manager input.
	 *
	 * @param string $formName Unique form name for Joomla's field machinery.
	 * @param string $group    POST field group, for example "style".
	 * @param string $name     Field name inside the group.
	 * @param string $value    Current field value.
	 * @param string $id       HTML field ID used by the surrounding label.
	 *
	 * @return string Rendered media field input.
	 */
	public static function imageInput(string $formName, string $group, string $name, string $value, string $id): string
	{
		$factory = Factory::getContainer()->get(FormFactoryInterface::class);
		$form = $factory->createForm($formName, ['control' => '']);
		$xml = sprintf(
			'<form><fields name="%s"><field name="%s" id="%s" type="media" types="images" preview="tooltip" /></fields></form>',
			htmlspecialchars($group, ENT_QUOTES | ENT_XML1, 'UTF-8'),
			htmlspecialchars($name, ENT_QUOTES | ENT_XML1, 'UTF-8'),
			htmlspecialchars($id, ENT_QUOTES | ENT_XML1, 'UTF-8')
		);

		if (!$form->load($xml))
		{
			throw new \RuntimeException('Unable to create Punga Mail media field.');
		}

		return $form->getInput($name, $group, $value);
	}
}
