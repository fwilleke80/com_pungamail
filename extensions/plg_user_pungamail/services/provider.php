<?php
/**
 * @package     Punga.Mail
 * @subpackage  Plugin.User
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Punga\Plugin\User\PungaMail\Extension\PungaMail;

return new class implements ServiceProviderInterface
{
	/** @return void */
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface
			{
				$plugin = new PungaMail((array) PluginHelper::getPlugin('user', 'pungamail'));
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
