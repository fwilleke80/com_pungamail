<?php
/**
 * @package     Punga.Mail
 * @subpackage  Module.Signup
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface
{
	/** @return void */
	public function register(Container $container): void
	{
		$container->registerServiceProvider(new ModuleDispatcherFactory('Punga\\Module\\PungaMailSignup'));
		$container->registerServiceProvider(new Module());
	}
};
