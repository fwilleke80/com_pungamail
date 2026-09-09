<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Central Punga Mail ACL helper.
 *
 * Joomla's standard component actions remain responsible for access to the
 * component and the Newsletter CRUD lifecycle. Punga Mail-specific actions
 * protect the larger functional areas which do not map cleanly to Joomla's
 * generic content actions.
 */
final class Permissions
{
	public const SEND_NEWSLETTERS = 'pungamail.newsletters.send';
	public const MANAGE_AUTOMATIC = 'pungamail.automatic.manage';
	public const MANAGE_AUDIENCE = 'pungamail.audience.manage';
	public const MANAGE_DESIGN = 'pungamail.design.manage';
	public const MANAGE_DELIVERY = 'pungamail.delivery.manage';
	public const MANAGE_TOOLS = 'pungamail.tools.manage';

	/**
	 * Tests a component permission, with component-level core.admin as the
	 * Joomla-standard full-control override.
	 *
	 * @param string $action ACL action name.
	 *
	 * @return bool
	 */
	public static function can(string $action): bool
	{
		$user = Factory::getApplication()->getIdentity();

		if ($user->authorise('core.admin', 'com_pungamail'))
		{
			return true;
		}

		if ($action === 'core.admin')
		{
			return false;
		}

		if ($action === 'core.manage' || $action === 'core.options')
		{
			return $user->authorise($action, 'com_pungamail');
		}

		if (!$user->authorise('core.manage', 'com_pungamail'))
		{
			return false;
		}

		return $user->authorise($action, 'com_pungamail');
	}

	/**
	 * Returns whether the user may open and save Punga Mail Component Options.
	 * Joomla separates option configuration (`core.options`) from ACL
	 * administration (`core.admin`).
	 *
	 * @return bool
	 */
	public static function canConfigure(): bool
	{
		$user = Factory::getApplication()->getIdentity();

		return $user->authorise('core.admin', 'com_pungamail')
			|| $user->authorise('core.options', 'com_pungamail');
	}

	/**
	 * Requires a Punga Mail permission.
	 *
	 * @param string $action ACL action name.
	 *
	 * @return void
	 */
	public static function require(string $action): void
	{
		if (!self::can($action))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/**
	 * Requires permission to configure Punga Mail Component Options.
	 *
	 * @return void
	 */
	public static function requireConfigure(): void
	{
		if (!self::canConfigure())
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/**
	 * Returns the ACL action required for an administrator section/view.
	 *
	 * @param string $contextView Public administrator view from the URL.
	 * @param string $screen      Sidebar-preserving child screen.
	 *
	 * @return string
	 */
	public static function actionForView(string $contextView, string $screen = ''): string
	{
		$view = $screen !== '' ? $screen : $contextView;

		return match ($view)
		{
			'digests', 'digest' => self::MANAGE_AUTOMATIC,
			'audience', 'subscribers', 'subscriber', 'topics', 'topic' => self::MANAGE_AUDIENCE,
			'design', 'templates', 'template', 'templatepreview', 'contentlayouts', 'contentlayout' => self::MANAGE_DESIGN,
			'delivery' => self::MANAGE_DELIVERY,
			'tools', 'import' => self::MANAGE_TOOLS,
			'preflight' => self::SEND_NEWSLETTERS,
			default => 'core.manage',
		};
	}

	/**
	 * Hides Punga Mail administrator submenu links which the current user cannot
	 * access. Joomla currently installs component submenu links as ordinary
	 * administrator menu rows and does not filter them by arbitrary component
	 * action names, so this complements (but never replaces) server-side ACL.
	 *
	 * @return void
	 */
	public static function prepareAdministratorMenu(): void
	{
		$allowedViews = [
			'dashboard' => self::can('core.manage'),
			'newsletters' => self::can('core.manage'),
			'digests' => self::can(self::MANAGE_AUTOMATIC),
			'audience' => self::can(self::MANAGE_AUDIENCE),
			'design' => self::can(self::MANAGE_DESIGN),
			'delivery' => self::can(self::MANAGE_DELIVERY),
			'tools' => self::can(self::MANAGE_TOOLS),
		];
		$blockedViews = array_keys(array_filter($allowedViews, static fn (bool $allowed): bool => !$allowed));

		if ($blockedViews === [])
		{
			return;
		}

		$encoded = json_encode(array_values($blockedViews), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
		Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript(<<<JS
(() => {
	const blocked = new Set({$encoded});
	const apply = () => {
		document.querySelectorAll('a[href*="option=com_pungamail"]').forEach((link) => {
			try {
				const url = new URL(link.href, window.location.href);
				const view = url.searchParams.get('view') || 'dashboard';
				if (!blocked.has(view)) {
					return;
				}
				const item = link.closest('li');
				if (item) {
					item.hidden = true;
				} else {
					link.hidden = true;
				}
			} catch (error) {
				// Ignore malformed or non-HTTP administrator links.
			}
		});
	};
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', apply, { once: true });
	} else {
		apply();
	}
})();
JS);
	}
}
