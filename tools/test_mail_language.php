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
		/** @param string $tag Language tag. @return array<string,string> */
		public static function getMetadata(string $tag): array
		{
			return [];
		}

		/** @param string $base Base directory. @param string $tag Language tag. @return string */
		public static function getLanguagePath(string $base, string $tag): string
		{
			return $base . '/language/' . $tag;
		}

		/** @return array<string,string> */
		public static function parseIniFile(string $path): array
		{
			$result = is_file($path) ? parse_ini_file($path, false, INI_SCANNER_RAW) : [];

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
	define('JPATH_ADMINISTRATOR', $site . '/administrator');
	define('JPATH_BASE', JPATH_ADMINISTRATOR);
	define('_JEXEC', 1);
	// Optional verification against unmodified Joomla upstream language classes.
	if (getenv('PUNGAMAIL_JOOMLA_LANGUAGE') && getenv('PUNGAMAIL_JOOMLA_BASE_LANGUAGE'))
	{
		require getenv('PUNGAMAIL_JOOMLA_BASE_LANGUAGE');
		require getenv('PUNGAMAIL_JOOMLA_LANGUAGE');
	}
	else
	{
		require __DIR__ . '/stubs/Language.php';
	}
	require dirname(__DIR__) . '/extensions/com_pungamail/administrator/components/com_pungamail/src/Service/WebsiteLanguage.php';
	require dirname(__DIR__) . '/extensions/com_pungamail/administrator/components/com_pungamail/src/Service/ContentTypeLabelService.php';
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


	$adminCatalog = $site . '/administrator/components/com_example/language/de-DE';
	$englishCatalog = $site . '/administrator/components/com_example/language/en-GB';
	mkdir($adminCatalog, 0777, true);
	mkdir($englishCatalog, 0777, true);
	mkdir($site . '/administrator/language/overrides', 0777, true);
	file_put_contents($adminCatalog . '/com_example.sys.ini', "COM_EXAMPLE_CONTENT_TYPE_ARTICLE=\"Artikel\"\nCOM_EXAMPLE_ARTICLE=\"Wrong direct alias\"\nCOM_EXAMPLE_FIELD_CONTEXT_POLL=\"Wrong context guess\"\nCOM_EXAMPLE_CONTENT_TYPE_EVENT=\"Termin\"\nCOM_EXAMPLE_CONTENT_TYPE_GROUP_ITEM=\"Nested type\"\nREGISTRY_TITLE=\"Wrong registry-key guess\"\n");
	file_put_contents($englishCatalog . '/com_example.sys.ini', "COM_EXAMPLE_CONTENT_TYPE_OTHER=\"English fallback\"\n");
	file_put_contents($adminCatalog . '/com_example.ini', "COM_EXAMPLE_CONTENT_TYPE_POLL=\"Wrong regular ini\"\n");
	file_put_contents($site . '/administrator/language/overrides/de-DE.override.ini', "COM_EXAMPLE_CONTENT_TYPE_ARTICLE=\"Wrong administrator override\"\nCOM_EXAMPLE_CONTENT_TYPE_ADMINONLY=\"Wrong inherited override\"\n");
	file_put_contents($overrides . '/de-DE.override.ini', "COM_EXAMPLE_CONTENT_TYPE_EVENT=\"Website event override\"\n", FILE_APPEND);
	foreach ([
		['com_example.article', 'Article', 'Artikel'],
		['com_example.poll', 'Poll', 'Poll'],
		['com_example.event', 'Event', 'Website event override'],
		['com_example.other', 'Other', 'English fallback'],
		['com_example.unknown', 'Registry fallback', 'Registry fallback'],
		['com_example.custom', 'REGISTRY_TITLE', 'REGISTRY_TITLE'],
		['com_example.group.item', 'Nested', 'Nested type'],
		['com_example.adminonly', 'Registry name', 'Registry name'],
	] as [$alias, $title, $expected])
	{
		if ($service->contentTypeLabel($alias, $title) !== $expected)
		{
			fwrite(STDERR, "[FAIL] Website content-type translation: " . $alias . "\n");
			exit(1);
		}
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
