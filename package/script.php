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

	/** @var bool Whether uninstall should remove all Punga Mail tables. */
	private bool $removeTablesOnUninstall = false;

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
		if (!$this->removeTablesOnUninstall)
		{
			return true;
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$tables = [
			'#__pungamail_events',
			'#__pungamail_digest_runs',
			'#__pungamail_digest_groups',
			'#__pungamail_digest_topics',
			'#__pungamail_digest_categories',
			'#__pungamail_digest_sources',
			'#__pungamail_digests',
			'#__pungamail_bounces',
			'#__pungamail_send_queue',
			'#__pungamail_preference_request_topics',
			'#__pungamail_preference_requests',
			'#__pungamail_newsletter_topics',
			'#__pungamail_subscriber_topics',
			'#__pungamail_topics',
			'#__pungamail_mail_settings',
			'#__pungamail_newsletter_groups',
			'#__pungamail_newsletter_sources',
			'#__pungamail_newsletter_items',
			'#__pungamail_newsletters',
			'#__pungamail_templates',
			'#__pungamail_suppressions',
			'#__pungamail_subscribers',
		];

		foreach ($tables as $table)
		{
			$db->dropTable($table, true);
		}

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

		if ($type === 'uninstall')
		{
			$this->removeTablesOnUninstall = $this->readRemoveTablesOption();
		}
		elseif ($type === 'update')
		{
			$this->repairSubscriberRecipientName();
		}

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
	 * Repairs the display-name column omitted from the historical update chain.
	 *
	 * Fresh installations already contain the column. Checking the real table
	 * first also makes a retry safe after an interrupted package update.
	 *
	 * @return void
	 */
	private function repairSubscriberRecipientName(): void
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$table = $db->replacePrefix('#__pungamail_subscribers');
		$columns = array_change_key_case($db->getTableColumns($table, true), CASE_LOWER);

		if (isset($columns['recipient_name']))
		{
			return;
		}

		$query = 'ALTER TABLE ' . $db->quoteName('#__pungamail_subscribers')
			. ' ADD COLUMN ' . $db->quoteName('recipient_name')
			. " VARCHAR(255) NOT NULL DEFAULT '' AFTER " . $db->quoteName('email');
		$db->setQuery($query)->execute();
	}

	/**
	 * Reads the component's destructive-uninstall preference while the component
	 * extension row is still available during package preflight.
	 *
	 * @return bool True only when explicitly enabled by an administrator.
	 */
	private function readRemoveTablesOption(): bool
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$type = 'component';
		$element = 'com_pungamail';
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = :type')
			->where($db->quoteName('element') . ' = :element')
			->bind(':type', $type)
			->bind(':element', $element);
		$params = json_decode((string) $db->setQuery($query)->loadResult(), true);

		return is_array($params) && (int) ($params['remove_tables_on_uninstall'] ?? 0) === 1;
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
