<?php
/**
 * @package Punga.Mail
 * @license MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Language\Language;

/** Resolves registry labels using Joomla ContenttypeField's naming and loading rules. */
final class ContentTypeLabelService
{
	/**
	 * @param Language $language Language context to use without changing the application.
	 * @param string $alias Registered type alias.
	 * @param string $title Original registry title, returned when no key exists.
	 * @return string Translated type name or unchanged registry title.
	 */
	public static function translate(Language $language, string $alias, string $title): string
	{
		$parts = explode('.', $alias);
		$component = array_shift($parts);
		if (preg_match('/^com_[a-z0-9_]+$/i', $component) !== 1)
		{
			return $title;
		}

		$admin = defined('JPATH_ADMINISTRATOR') ? JPATH_ADMINISTRATOR : JPATH_SITE . '/administrator';
		$language->load($component . '.sys', $admin)
			|| $language->load($component . '.sys', $admin . '/components/' . $component);
		$key = $component . '_CONTENT_TYPE_' . implode('_', $parts);

		return $language->hasKey($key) ? $language->_($key) : $title;
	}
}
