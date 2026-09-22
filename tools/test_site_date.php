<?php
/** Regression test for site-localized newsletter dates. */

declare(strict_types=1);

namespace Joomla\CMS\Component
{
	final class ComponentHelper
	{
		public static function getParams(string $option): object
		{
			return new class($option)
			{
				public function __construct(private readonly string $option)
				{
				}

				public function get(string $key, mixed $default = null): mixed
				{
					return $this->option === 'com_languages' && $key === 'site' ? 'de-DE' : $default;
				}
			};
		}
	}
}

namespace Joomla\CMS\Date
{
	class Date extends \DateTime
	{
		public function __construct(string $time = 'now', \DateTimeZone|string|null $timezone = null)
		{
			$timezone = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
			parent::__construct($time === 'now' ? '2026-09-22 22:30:00' : $time, $timezone);
		}

		public function format(string $format, bool $local = false, bool $translate = true): string
		{
			return parent::format($format);
		}
	}
}

namespace Joomla\CMS\Language
{
	interface LanguageFactoryInterface
	{
		public function createLanguage(string $lang, bool $debug = false): Language;
	}

	class Language
	{
		public function load(string $extension, string $basePath, ?string $lang = null, bool $reload = false): bool
		{
			return true;
		}

		public function _(string $key): string
		{
			return match ($key)
			{
				'DATE_FORMAT_LC3' => 'd. F Y',
				'SEPTEMBER' => 'September',
				default => $key,
			};
		}

		public function getTag(): string
		{
			return 'de-DE';
		}
	}
}

namespace Joomla\CMS
{
	use Joomla\CMS\Language\Language;
	use Joomla\CMS\Language\LanguageFactoryInterface;

	final class Factory
	{
		public static function getApplication(): object
		{
			return new class
			{
				public function get(string $key, mixed $default = null): mixed
				{
					return match ($key)
					{
						'offset' => 'Europe/Berlin',
						'language' => 'de-DE',
						default => $default,
					};
				}
			};
		}

		public static function getContainer(): object
		{
			return new class
			{
				public function get(string $class): object
				{
					if ($class !== LanguageFactoryInterface::class)
					{
						throw new \RuntimeException('Unexpected container lookup: ' . $class);
					}

					return new class implements LanguageFactoryInterface
					{
						public function createLanguage(string $lang, bool $debug = false): Language
						{
							return new Language();
						}
					};
				}
			};
		}
	}
}

namespace
{
	use Punga\Component\PungaMail\Administrator\Service\SiteDateService;

	define('JPATH_SITE', sys_get_temp_dir());
	require_once dirname(__DIR__) . '/extensions/com_pungamail/administrator/components/com_pungamail/src/Service/SiteDateService.php';
	$result = (new SiteDateService())->format();

	if ($result !== '23. September 2026')
	{
		fwrite(STDERR, '[FAIL] Site-localized date was ' . $result . ', expected 23. September 2026' . PHP_EOL);
		exit(1);
	}

	fwrite(STDOUT, "[OK] Site-localized date regression test passed\n");
}
