<?php
/** @package Punga.Mail @license MIT */
namespace Joomla\CMS\Language;

/** Minimal filesystem-backed Joomla language fixture for isolated tests. */
class Language
{
	protected array $strings = [];
	protected array $override = [];
	protected string $lang;

	/** @param string $lang Language tag. @param bool $debug Debug flag. */
	public function __construct(string $lang, bool $debug = false)
	{
		$this->lang = $lang;
		$this->override = $this->parse(JPATH_BASE . '/language/overrides/' . $lang . '.override.ini');
		$this->strings = $this->override;
	}

	/** @param string $path INI path. @return array<string,string> */
	protected function parse(string $path): array
	{
		return is_file($path) ? LanguageHelper::parseIniFile($path) : [];
	}

	/** @param string $extension Catalog. @param string $basePath Base. @param string|null $lang Tag. @param bool $reload Reload. @param bool $default Fallback. @return bool */
	public function load(string $extension, string $basePath, ?string $lang = null, bool $reload = false, bool $default = true): bool
	{
		$lang ??= $this->lang;
		if ($lang !== 'en-GB' && $default)
		{
			$this->load($extension, $basePath, 'en-GB', false, true);
		}
		foreach ([$extension . '.ini', $lang . '.' . $extension . '.ini'] as $file)
		{
			$strings = $this->parse($basePath . '/language/' . $lang . '/' . $file);
			if ($strings !== [])
			{
				$this->strings = array_replace($this->strings, $strings, $this->override);
				return true;
			}
		}
		return false;
	}

	/** @param string $key Translation key. @return bool */
	public function hasKey(string $key): bool
	{
		return isset($this->strings[strtoupper($key)]);
	}

	/** @param string $key Translation key. @return string */
	public function _(string $key): string
	{
		return $this->strings[strtoupper($key)] ?? $key;
	}
}
