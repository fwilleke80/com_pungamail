<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Punga\Component\PungaMail\Administrator\Extension\PungaMailComponent;

return new class implements ServiceProviderInterface
{
	/**
	 * Registers the component services.
	 *
	 * @param Container $container Dependency injection container.
	 *
	 * @return void
	 */
	public function register(Container $container): void
	{
		$container->registerServiceProvider(new ComponentDispatcherFactory('Punga\\Component\\PungaMail'));
		$container->registerServiceProvider(new MVCFactory('Punga\\Component\\PungaMail'));

		$container->set(
			ComponentInterface::class,
			static function (Container $container): ComponentInterface
			{
				$component = new PungaMailComponent($container->get(ComponentDispatcherFactoryInterface::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));

				return $component;
			}
		);
	}
};
