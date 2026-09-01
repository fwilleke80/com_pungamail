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
	public static function templates(): string
	{
		return 'index.php?option=com_pungamail&view=templates';
	}

	/**
	 * @param int $id Template ID, or zero for a new template.
	 * @return string
	 */
	public static function template(int $id = 0): string
	{
		return self::templates() . '&screen=template' . ($id > 0 ? '&id=' . $id : '');
	}

	/** @return string */
	public static function templatePreview(int $id): string
	{
		return self::templates() . '&screen=templatepreview&id=' . $id;
	}
}
