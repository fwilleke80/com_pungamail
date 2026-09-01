<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;

/**
 * Resolves subscriber-facing mail strings from the Joomla site language.
 *
 * Newsletter rendering also happens in the administrator and Scheduled Tasks
 * applications. Relying on the current application's Text catalog would make
 * administrator overrides unexpectedly control public newsletter copy. This
 * service deliberately loads the frontend component catalog and the frontend
 * language-override file so Joomla Website language overrides are authoritative
 * for subscriber-facing mail strings in every execution context.
 */
final class MailTextService
{
	/** @var array<string,string>|null */
	private ?array $strings = null;

	/**
	 * Returns one localized subscriber-facing mail string.
	 *
	 * @param string $key Joomla language key.
	 *
	 * @return string Localized text, or Joomla's normal Text fallback.
	 */
	public function text(string $key): string
	{
		$strings = $this->strings();

		return array_key_exists($key, $strings) ? $strings[$key] : Text::_($key);
	}

	/**
	 * Loads the frontend language catalog plus Website language overrides.
	 *
	 * @return array<string,string> Effective frontend strings.
	 */
	private function strings(): array
	{
		if ($this->strings !== null)
		{
			return $this->strings;
		}

		$this->strings = [];

		// The isolated CLI renderer tests intentionally run without a Joomla
		// filesystem. Fall back to the current Text catalog in that environment.
		if (!defined('JPATH_SITE'))
		{
			return $this->strings;
		}

		$siteLanguage = trim((string) ComponentHelper::getParams('com_languages')->get('site', ''));

		if ($siteLanguage === '')
		{
			$siteLanguage = trim((string) Factory::getApplication()->get('language', 'en-GB'));
		}

		if ($siteLanguage === '')
		{
			$siteLanguage = 'en-GB';
		}

		// English is the component fallback; the configured frontend language
		// overlays it, followed by Joomla's Website language overrides.
		$this->loadTag('en-GB');

		if ($siteLanguage !== 'en-GB')
		{
			$this->loadTag($siteLanguage);
		}

		$override = JPATH_SITE . '/language/overrides/' . $siteLanguage . '.override.ini';
		$this->strings = array_replace($this->strings, $this->parse($override));

		return $this->strings;
	}

	/**
	 * Loads component-local and global frontend catalogs for one language tag.
	 *
	 * @param string $tag Joomla language tag.
	 *
	 * @return void
	 */
	private function loadTag(string $tag): void
	{
		$paths = [
			JPATH_SITE . '/components/com_pungamail/language/' . $tag . '/com_pungamail.ini',
			JPATH_SITE . '/language/' . $tag . '/' . $tag . '.com_pungamail.ini',
		];

		foreach ($paths as $path)
		{
			$this->strings = array_replace($this->strings ?? [], $this->parse($path));
		}
	}

	/**
	 * Parses one Joomla INI language file.
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return array<string,string> Parsed strings.
	 */
	private function parse(string $path): array
	{
		if (!is_file($path))
		{
			return [];
		}

		/** @var array<string,string> $strings */
		$strings = LanguageHelper::parseIniFile($path);

		return $strings;
	}
}
