<?php
/**
 * @package     Punga.Mail
 * @subpackage  Plugin.System
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Plugin\System\PungaMailCampaign\Extension;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Recognizes trusted internal Punga Mail campaign visits and dispatches integration events. */
final class PungaMailCampaign extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;

	/** @return array<string,string> */
	public static function getSubscribedEvents(): array
	{
		return ['onAfterRoute' => 'onAfterRoute'];
	}

	/** @return void */
	public function onAfterRoute(Event $event): void
	{
		$app = $this->getApplication();

		if (!$app->isClient('site'))
		{
			return;
		}

		$app->bootComponent('com_pungamail');
		$input = $app->getInput();
		$token = trim($input->getString('pm_track'));

		if ($token === '')
		{
			return;
		}

		$data = ServiceFactory::tokens()->validateCampaignToken($token);

		if ($data === null)
		{
			return;
		}

		foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_content'] as $name)
		{
			if ($input->getString($name) !== (string) $data[$name])
			{
				return;
			}
		}

		$visit = $data + [
			'url' => Uri::getInstance()->toString(),
			'path' => Uri::getInstance()->getPath(),
			'timestamp_utc' => (new Date('now', 'UTC'))->toSql(),
		];
		$userAgent = (string) ($input->server->getString('HTTP_USER_AGENT', '') ?? '');
		ServiceFactory::statistics()->recordCampaignClick($visit, $userAgent);

		// Domain-specific integration event for extensions that want the complete
		// signed campaign context without depending on Punga Analytics.
		$this->getDispatcher()->dispatch(
			'onPungaMailCampaignVisit',
			new Event('onPungaMailCampaignVisit', ['data' => $visit])
		);

		// Standard Punga Analytics bridge, matching the event contract already
		// used by other Punga extensions such as Punga Audio Archive.
		$newsletter = ServiceFactory::newsletters()->find((int) ($data['newsletter_id'] ?? 0));
		$linkLabel = trim((string) ($data['utm_content'] ?? ''));
		if ($linkLabel === '')
		{
			$linkLabel = trim((string) ($visit['path'] ?? ''));
		}
		$itemTitle = trim((string) ($newsletter->title ?? ''));
		if ($linkLabel !== '')
		{
			$itemTitle = $itemTitle !== '' ? $itemTitle . ' — ' . $linkLabel : $linkLabel;
		}
		$this->getDispatcher()->dispatch(
			'onPungaAnalyticsRecord',
			new Event('onPungaAnalyticsRecord', [
				'event_type' => 'mail.click',
				'component' => 'com_pungamail',
				'view_name' => $input->getCmd('view', ''),
				'path' => (string) ($visit['path'] ?? ''),
				'item_type' => 'pungamail.newsletter_link',
				'item_id' => (string) ((int) ($data['newsletter_id'] ?? 0)) . ':' . (string) ((int) ($data['link_index'] ?? 0)),
				'item_title' => $itemTitle,
			])
		);
	}
}
