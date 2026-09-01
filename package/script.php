<?php
/**
 * @package     Punga.Mail
 * @subpackage  Installer
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

return new class () implements InstallerScriptInterface
{
	/**
	 * Minimum Joomla version supported by this package.
	 *
	 * @var string
	 */
	private string $minimumJoomla = '6.0.0';

	/**
	 * Minimum PHP version supported by Joomla 6.
	 *
	 * @var string
	 */
	private string $minimumPhp = '8.3.0';

	/**
	 * Runs after a fresh package installation.
	 *
	 * @param InstallerAdapter $adapter Installer adapter.
	 *
	 * @return bool True on success.
	 */
	public function install(InstallerAdapter $adapter): bool
	{
		return true;
	}

	/**
	 * Runs after a package update.
	 *
	 * @param InstallerAdapter $adapter Installer adapter.
	 *
	 * @return bool True on success.
	 */
	public function update(InstallerAdapter $adapter): bool
	{
		return true;
	}

	/**
	 * Runs while the package is being uninstalled.
	 *
	 * Punga Mail intentionally does not remove subscriber, newsletter, queue,
	 * or audit data during uninstall. An explicit purge script is provided in
	 * the source tree for administrators who want destructive removal.
	 *
	 * @param InstallerAdapter $adapter Installer adapter.
	 *
	 * @return bool True on success.
	 */
	public function uninstall(InstallerAdapter $adapter): bool
	{
		return true;
	}

	/**
	 * Validates platform requirements before Joomla changes the installation.
	 *
	 * @param string           $type    Installer operation.
	 * @param InstallerAdapter $adapter Installer adapter.
	 *
	 * @return bool True when the platform satisfies Punga Mail requirements.
	 */
	public function preflight(string $type, InstallerAdapter $adapter): bool
	{
		$app = Factory::getApplication();

		if (version_compare(PHP_VERSION, $this->minimumPhp, '<'))
		{
			$app->enqueueMessage(
				sprintf(Text::_('JLIB_INSTALLER_MINIMUM_PHP'), $this->minimumPhp),
				'error'
			);

			return false;
		}

		if (version_compare(JVERSION, $this->minimumJoomla, '<'))
		{
			$app->enqueueMessage(
				sprintf(Text::_('JLIB_INSTALLER_MINIMUM_JOOMLA'), $this->minimumJoomla),
				'error'
			);

			return false;
		}

		return true;
	}

	/**
	 * Enables the package plugins after installation or update.
	 *
	 * @param string           $type    Installer operation.
	 * @param InstallerAdapter $adapter Installer adapter.
	 *
	 * @return bool True on success.
	 */
	public function postflight(string $type, InstallerAdapter $adapter): bool
	{
		if (!in_array($type, ['install', 'update'], true))
		{
			return true;
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . ' = 1')
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('element') . ' = ' . $db->quote('pungamail'))
			->where('(' . $db->quoteName('folder') . ' = ' . $db->quote('user') . ' OR ' . $db->quoteName('folder') . ' = ' . $db->quote('task') . ')');

		$db->setQuery($query)->execute();

		return true;
	}
};
