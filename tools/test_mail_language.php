<?php
/**
 * @package   Punga.Mail
 * @copyright Copyright (c) 2026 Punga
 * @license   MIT
 */

declare(strict_types=1);

namespace Joomla\CMS\Component
{
	/** Minimal component helper stub for frontend-language testing. */
	final class ComponentHelper
	{
		/** @return object Parameter stub. */
		public static function getParams(string $option): object
		{
			return new class($option)
			{
				/** @param string $option Component option. */
				public function __construct(private readonly string $option)
				{
				}

				/** @return mixed */
				public function get(string $key, mixed $default = null): mixed
				{
					return $this->option === 'com_languages' && $key === 'site' ? 'de-DE' : $default;
				}
			};
		}
	}
}

namespace Joomla\CMS\Language
{
	/** Minimal Joomla language-file parser stub. */
	final class LanguageHelper
	{
		/** @return array<string,string> */
		public static function parseIniFile(string $path): array
		{
			$result = parse_ini_file($path, false, INI_SCANNER_RAW);

			return is_array($result) ? array_map(static fn (mixed $value): string => (string) $value, $result) : [];
		}
	}

	/** Minimal Text fallback stub. */
	final class Text
	{
		/** @return string */
		public static function _(string $key): string
		{
			return 'fallback:' . $key;
		}
	}
}

namespace Joomla\CMS
{
	/** Minimal application factory stub. */
	final class Factory
	{
		/** @return object */
		public static function getApplication(): object
		{
			return new class
			{
				/** @return mixed */
				public function get(string $key, mixed $default = null): mixed
				{
					return $default;
				}
			};
		}
	}
}

namespace
{
	use Punga\Component\PungaMail\Administrator\Service\MailTextService;

	$site = sys_get_temp_dir() . '/pungamail-language-test-' . bin2hex(random_bytes(6));
	define('JPATH_SITE', $site);
	$componentLanguage = $site . '/components/com_pungamail/language/de-DE';
	$overrides = $site . '/language/overrides';
	mkdir($componentLanguage, 0777, true);
	mkdir($overrides, 0777, true);
	file_put_contents($componentLanguage . '/com_pungamail.ini', "COM_PUNGAMAIL_MAIL_FOOTER_REASON=\"Site German default\"\nCOM_PUNGAMAIL_MAIL_UNSUBSCRIBE=\"Abmelden\"\n");
	file_put_contents($overrides . '/de-DE.override.ini', "COM_PUNGAMAIL_MAIL_FOOTER_REASON=\"Website override wins\"\n");

	require_once dirname(__DIR__) . '/extensions/com_pungamail/administrator/components/com_pungamail/src/Service/MailTextService.php';
	$service = new MailTextService();

	if ($service->text('COM_PUNGAMAIL_MAIL_FOOTER_REASON') !== 'Website override wins')
	{
		fwrite(STDERR, "[FAIL] Website language override did not override the site component string.\n");
		exit(1);
	}

	if ($service->text('COM_PUNGAMAIL_MAIL_UNSUBSCRIBE') !== 'Abmelden')
	{
		fwrite(STDERR, "[FAIL] Frontend component language string was not loaded.\n");
		exit(1);
	}

	if ($service->text('COM_PUNGAMAIL_UNKNOWN') !== 'fallback:COM_PUNGAMAIL_UNKNOWN')
	{
		fwrite(STDERR, "[FAIL] Unknown language strings did not use Joomla Text fallback.\n");
		exit(1);
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($site, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($iterator as $entry)
	{
		$entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
	}
	rmdir($site);

	fwrite(STDOUT, "[OK] Website mail-language override regression test passed\n");
}
