<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */
namespace Punga\Component\PungaMail\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;

/** SEF router for Punga Mail public subscription flows. */
final class Router extends RouterView
{
	/** Constructor. */
	public function __construct(SiteApplication $app, AbstractMenu $menu, ?CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
	{
		$subscription = new RouterViewConfiguration('subscription');
		$this->registerView($subscription);
		$this->registerView((new RouterViewConfiguration('confirm'))->setParent($subscription));
		$this->registerView((new RouterViewConfiguration('unsubscribe'))->setParent($subscription));
		$this->registerView((new RouterViewConfiguration('message'))->setParent($subscription));
		parent::__construct($app, $menu);
		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}
}
