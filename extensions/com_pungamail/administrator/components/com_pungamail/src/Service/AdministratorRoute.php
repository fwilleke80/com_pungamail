<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

/**
 * Centralizes administrator URLs that must preserve Joomla sidebar context.
 *
 * Joomla's administrator menu highlights submenu entries from the browser URL.
 * Secondary Punga Mail screens therefore retain the owning list view in the
 * public URL and select their actual MVC view through the private `screen`
 * parameter. DisplayController resolves that parameter before MVC rendering.
 */
final class AdministratorRoute
{
	/** @return string */
	public static function newsletters(): string
	{
		return 'index.php?option=com_pungamail&view=newsletters';
	}

	/**
	 * @param int $id Newsletter ID, or zero for a new draft.
	 * @return string
	 */
	public static function newsletter(int $id = 0): string
	{
		return self::newsletters() . '&screen=newsletter' . ($id > 0 ? '&id=' . $id : '');
	}

	/** @return string */
	public static function preview(int $id): string
	{
		return self::newsletters() . '&screen=preview&id=' . $id;
	}

	/** @return string */
	public static function preflight(int $id): string
	{
		return self::newsletters() . '&screen=preflight&id=' . $id;
	}

	/** @return string */
	public static function audience(): string
	{
		return 'index.php?option=com_pungamail&view=audience';
	}

	/** @return string */
	public static function subscribers(): string
	{
		return self::audience() . '&screen=subscribers';
	}

	/** @return string */
	public static function subscriber(int $id = 0): string
	{
		return self::audience() . '&screen=subscriber' . ($id > 0 ? '&id=' . $id : '');
	}

	/** @return string */
	public static function design(): string
	{
		return 'index.php?option=com_pungamail&view=design';
	}

	/** @return string */
	public static function templates(): string
	{
		return self::design() . '&screen=templates';
	}

	/**
	 * @param int $id Template ID, or zero for a new template.
	 * @return string
	 */
	public static function template(int $id = 0): string
	{
		return self::design() . '&screen=template' . ($id > 0 ? '&id=' . $id : '');
	}

	/** @return string */
	public static function templatePreview(int $id): string
	{
		return self::design() . '&screen=templatepreview&id=' . $id;
	}

	/** @return string */
	public static function contentLayouts(): string
	{
		return self::design() . '&screen=contentlayouts';
	}

	/** @return string */
	public static function contentLayout(string $sourceKey): string
	{
		return self::design() . '&screen=contentlayout&source_key=' . rawurlencode($sourceKey);
	}

	/** @return string */
	public static function topics(): string
	{
		return self::audience() . '&screen=topics';
	}

	/** @return string */
	public static function topic(int $id = 0): string
	{
		return self::audience() . '&screen=topic' . ($id > 0 ? '&id=' . $id : '');
	}

	/** @return string */
	public static function digests(): string
	{
		return 'index.php?option=com_pungamail&view=digests';
	}

	/** @return string */
	public static function digest(int $id = 0): string
	{
		return self::digests() . '&screen=digest' . ($id > 0 ? '&id=' . $id : '');
	}
	/** @return string */
	public static function tools(): string
	{
		return 'index.php?option=com_pungamail&view=tools&screen=import';
	}

}
