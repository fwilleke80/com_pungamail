<?php
/**
 * @package     Punga.Mail
 * @subpackage  Task.Plugin
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Punga\Plugin\Task\PungaMail\Extension\PungaMail;

return new class implements ServiceProviderInterface
{
	/**
	 * Registers the task plugin with Joomla's dependency injection container.
	 *
	 * @param Container $container Joomla DI container.
	 *
	 * @return void
	 */
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface
			{
				$plugin = new PungaMail(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('task', 'pungamail')
				);
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
