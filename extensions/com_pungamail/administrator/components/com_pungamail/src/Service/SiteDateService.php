<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use DateTimeZone;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageFactoryInterface;

/** Formats newsletter-facing dates using the Joomla site's language and timezone. */
final class SiteDateService
{
	private bool $languageResolved = false;
	private ?Language $resolvedLanguage = null;

	/**
	 * Formats a date using the site's DATE_FORMAT_LC3 convention.
	 *
	 * @param Date|null $date UTC/current date. Null means now.
	 *
	 * @return string Localized site-facing date.
	 */
	public function format(?Date $date = null): string
	{
		$date ??= new Date('now', 'UTC');

		return $this->formatWithPattern($date, $this->dateFormat());
	}

	/**
	 * Formats a date/time using the site's DATE_FORMAT_LC5 convention.
	 *
	 * @param Date $date Date/time value.
	 *
	 * @return string Localized site-facing date/time.
	 */
	public function formatDateTime(Date $date): string
	{
		return $this->formatWithPattern($date, $this->dateTimeFormat());
	}

	/** @return string Site DATE_FORMAT_LC3 pattern. */
	public function dateFormat(): string
	{
		return $this->siteFormat('DATE_FORMAT_LC3', 'd F Y');
	}

	/** @return string Site DATE_FORMAT_LC5 pattern. */
	public function dateTimeFormat(): string
	{
		return $this->siteFormat('DATE_FORMAT_LC5', 'd F Y H:i');
	}

	/**
	 * Formats a date with a site-language pattern in the site timezone.
	 *
	 * @param Date   $date   Date/time value.
	 * @param string $format PHP/Joomla date format.
	 *
	 * @return string Localized site-facing value.
	 */
	private function formatWithPattern(Date $date, string $format): string
	{
		$date = clone $date;
		$timezoneName = $this->timezoneName();
		$date->setTimezone(new DateTimeZone($timezoneName));
		$language = $this->siteLanguage();

		if ($language !== null && class_exists(\IntlDateFormatter::class))
		{
			$locale = str_replace('-', '_', $language->getTag());
			$formatter = new \IntlDateFormatter(
				$locale,
				\IntlDateFormatter::NONE,
				\IntlDateFormatter::NONE,
				$timezoneName,
				null,
				$this->phpDateFormatToIcu($format)
			);
			$formatted = $formatter->format($date->getTimestamp());

			if (is_string($formatted) && trim($formatted) !== '')
			{
				return $formatted;
			}
		}

		return $this->formatWithLanguage($date, $format, $language);
	}

	/** @return string */
	private function timezoneName(): string
	{
		$timezoneName = trim((string) Factory::getApplication()->get('offset', 'UTC')) ?: 'UTC';

		try
		{
			new DateTimeZone($timezoneName);
		}
		catch (\Throwable)
		{
			return 'UTC';
		}

		return $timezoneName;
	}

	/** @return string */
	private function siteFormat(string $key, string $fallback): string
	{
		$language = $this->siteLanguage();
		$format = $language !== null ? trim((string) $language->_($key)) : '';

		return $format === '' || $format === $key ? $fallback : $format;
	}

	/** @return Language|null */
	private function siteLanguage(): ?Language
	{
		if ($this->languageResolved)
		{
			return $this->resolvedLanguage;
		}

		$this->languageResolved = true;

		if (!defined('JPATH_SITE'))
		{
			return null;
		}

		$tag = trim((string) ComponentHelper::getParams('com_languages')->get('site', ''));

		if ($tag === '')
		{
			$tag = trim((string) Factory::getApplication()->get('language', 'en-GB')) ?: 'en-GB';
		}

		try
		{
			$language = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage($tag, false);
			$language->load('joomla', JPATH_SITE, $tag, true);
			$this->resolvedLanguage = $language;
		}
		catch (\Throwable)
		{
			$this->resolvedLanguage = null;
		}

		return $this->resolvedLanguage;
	}

	/**
	 * Minimal PHP-date to ICU conversion for Joomla's human-readable date formats.
	 *
	 * @param string $format PHP/Joomla date format.
	 *
	 * @return string ICU date pattern.
	 */
	private function phpDateFormatToIcu(string $format): string
	{
		$map = [
			'd' => 'dd', 'j' => 'd', 'F' => 'MMMM', 'M' => 'MMM',
			'm' => 'MM', 'n' => 'M', 'Y' => 'yyyy', 'y' => 'yy',
			'l' => 'EEEE', 'D' => 'EEE', 'H' => 'HH', 'G' => 'H',
			'h' => 'hh', 'g' => 'h', 'i' => 'mm', 's' => 'ss', 'A' => 'a', 'a' => 'a',
		];
		$result = '';
		$escaped = false;

		foreach (str_split($format) as $character)
		{
			if ($escaped)
			{
				$result .= "'" . str_replace("'", "''", $character) . "'";
				$escaped = false;
				continue;
			}

			if ($character === '\\')
			{
				$escaped = true;
				continue;
			}

			$result .= $map[$character] ?? (ctype_alpha($character) ? "'" . $character . "'" : $character);
		}

		return $result;
	}

	/**
	 * Formats with Joomla language keys when PHP Intl is unavailable.
	 *
	 * @param Date          $date     Site-timezone date.
	 * @param string        $format   PHP/Joomla date format.
	 * @param Language|null $language Site language object.
	 *
	 * @return string Localized date.
	 */
	private function formatWithLanguage(Date $date, string $format, ?Language $language): string
	{
		$result = '';
		$escaped = false;

		foreach (str_split($format) as $character)
		{
			if ($escaped)
			{
				$result .= $character;
				$escaped = false;
				continue;
			}

			if ($character === '\\')
			{
				$escaped = true;
				continue;
			}

			if ($language !== null && in_array($character, ['F', 'M', 'l', 'D'], true))
			{
				$full = $character === 'F' || $character === 'M'
					? strtoupper($date->format('F', true, false))
					: strtoupper($date->format('l', true, false));
				$key = in_array($character, ['M', 'D'], true) ? $full . '_SHORT' : $full;
				$translated = (string) $language->_($key);
				$result .= $translated !== $key ? $translated : $date->format($character, true, false);
				continue;
			}

			$result .= $date->format($character, true, false);
		}

		return $result;
	}
}
