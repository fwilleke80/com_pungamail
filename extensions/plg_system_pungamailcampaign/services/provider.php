<?php
/** @package Punga.Mail @subpackage Plugin.System */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Punga\Plugin\System\PungaMailCampaign\Extension\PungaMailCampaign;

return new class implements ServiceProviderInterface
{
	/** @return void */
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface
			{
				$plugin = new PungaMailCampaign(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('system', 'pungamailcampaign')
				);
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
