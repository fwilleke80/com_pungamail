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
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** SEF router for Punga Mail public subscription flows. */
final class Router extends RouterView
{
	private DatabaseInterface $db;
	/** Constructor. */
	public function __construct(SiteApplication $app, AbstractMenu $menu, ?CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
	{
		$this->db = $db;
		$subscription = new RouterViewConfiguration('subscription');
		$this->registerView($subscription);
		$this->registerView((new RouterViewConfiguration('confirm'))->setParent($subscription));
		$this->registerView((new RouterViewConfiguration('unsubscribe'))->setParent($subscription));
		$this->registerView((new RouterViewConfiguration('message'))->setParent($subscription));
		$this->registerView((new RouterViewConfiguration('browser'))->setParent($subscription));
		$archive = new RouterViewConfiguration('archive');
		$this->registerView($archive);
		$this->registerView((new RouterViewConfiguration('archiveitem'))->setKey('id')->setParent($archive));
		parent::__construct($app, $menu);
		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	/** @return array<int,string> */
	public function getArchiveitemSegment($id, $query): array
	{
		$id = (int) $id;
		if ($id <= 0)
		{
			return [];
		}

		$db = $this->getDatabase();
		$queryObject = $db->getQuery(true)
			->select(['snapshot_subject', 'title'])
			->from($db->quoteName('#__pungamail_newsletters'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$row = $db->setQuery($queryObject)->loadObject();
		$title = trim((string) ($row->snapshot_subject ?? $row->title ?? ''));
		$alias = OutputFilter::stringURLSafe($title);
		$segment = (string) $id;
		if ($alias !== '')
		{
			$segment .= '-' . $alias;
		}

		return [$id => $segment];
	}

	/** @return int */
	public function getArchiveitemId($segment, $query): int
	{
		return preg_match('/^(\d+)(?:-|$)/', (string) $segment, $matches) === 1 ? (int) $matches[1] : 0;
	}

	/** @return DatabaseInterface */
	private function getDatabase(): DatabaseInterface
	{
		return $this->db;
	}

}
