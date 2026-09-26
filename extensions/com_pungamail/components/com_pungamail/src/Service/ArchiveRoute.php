<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Resolves the published, accessible Newsletter Archive menu item. */
final class ArchiveRoute
{
	/** @return int */
	public static function menuItemId(): int
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();
		$levels = array_values(array_unique(array_map('intval', $user->getAuthorisedViewLevels())));

		if ($levels === [])
		{
			return 0;
		}

		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$clientId = 0;
		$published = 1;
		$link = 'index.php?option=com_pungamail&view=archive';
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('client_id') . ' = :clientId')
			->where($db->quoteName('published') . ' = :published')
			->where($db->quoteName('link') . ' = :link')
			->whereIn($db->quoteName('access'), $levels, ParameterType::INTEGER)
			->bind(':clientId', $clientId, ParameterType::INTEGER)
			->bind(':published', $published, ParameterType::INTEGER)
			->bind(':link', $link);

		if (Multilanguage::isEnabled())
		{
			$language = Factory::getLanguage()->getTag();
			$query->whereIn($db->quoteName('language'), ['*', $language], ParameterType::STRING)
				->order('CASE WHEN ' . $db->quoteName('language') . ' = ' . $db->quote($language) . ' THEN 0 ELSE 1 END');
		}

		$query->order($db->quoteName('id') . ' ASC');
		return (int) $db->setQuery($query, 0, 1)->loadResult();
	}

	/** @return string|null */
	public static function archiveUrl(): ?string
	{
		$itemId = self::menuItemId();

		if ($itemId <= 0)
		{
			return null;
		}

		return Route::_('index.php?option=com_pungamail&view=archive&Itemid=' . $itemId);
	}

	/** @return string */
	public static function itemUrl(int $newsletterId, int $itemId = 0): string
	{
		$link = 'index.php?option=com_pungamail&view=archiveitem&id=' . $newsletterId;

		if ($itemId > 0)
		{
			$link .= '&Itemid=' . $itemId;
		}

		return Route::_($link);
	}
}
