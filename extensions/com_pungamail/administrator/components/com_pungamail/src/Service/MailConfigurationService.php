<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/** Resolves inherited message metadata independently from visual styles. */
final class MailConfigurationService
{
	private const DEFAULT_NEW_CONTENT_ITEM_TEMPLATE = "### {title_link}\n\n{excerpt}\n\n{read_more}";

	/** @param MailSettingsRepository $mailSettings Bounce/envelope settings. */
	public function __construct(private readonly MailSettingsRepository $mailSettings)
	{
	}

	/** @return string Empty means intentionally render no body heading. */
	public function heading(?object $template, object $newsletter): string
	{
		$params = ComponentHelper::getParams('com_pungamail');
		$mode = 'custom';
		$value = (string) $params->get('mail_heading', '{site_name}');

		foreach ([$template, $newsletter] as $layer)
		{
			if ($layer === null)
			{
				continue;
			}

			$layerMode = (string) ($layer->heading_mode ?? 'inherit');

			if ($layerMode === 'inherit')
			{
				continue;
			}

			$mode = $layerMode;
			$value = (string) ($layer->mail_heading ?? '');
		}

		if ($mode === 'none')
		{
			return '';
		}

		if ($mode === 'site')
		{
			return (string) Factory::getApplication()->get('sitename');
		}

		return str_replace('{site_name}', (string) Factory::getApplication()->get('sitename'), trim($value));
	}

	/** @return string */
	public function newContentItemTemplate(?object $template, object $newsletter): string
	{
		$value = trim((string) ComponentHelper::getParams('com_pungamail')->get('new_content_item_template', self::DEFAULT_NEW_CONTENT_ITEM_TEMPLATE));

		if ($value === '')
		{
			$value = self::DEFAULT_NEW_CONTENT_ITEM_TEMPLATE;
		}

		foreach ([$template, $newsletter] as $layer)
		{
			$override = $layer !== null ? trim((string) ($layer->new_content_item_template ?? '')) : '';

			if ($override !== '')
			{
				$value = $override;
			}
		}

		return $value;
	}

	/** @return bool */
	public function browserView(?object $template, object $newsletter): bool
	{
		$value = (int) ComponentHelper::getParams('com_pungamail')->get('browser_view_enabled', 0);

		foreach ([$template, $newsletter] as $layer)
		{
			$override = $layer !== null ? (int) ($layer->browser_view ?? -1) : -1;

			if ($override !== -1)
			{
				$value = $override;
			}
		}

		return $value === 1;
	}

	/** @return array{email:string,name:string} */
	public function replyTo(?object $template, object $newsletter): array
	{
		$params = ComponentHelper::getParams('com_pungamail');
		$mode = (string) $params->get('reply_to_mode', 'none');
		$email = trim((string) $params->get('reply_to_email', ''));
		$name = trim((string) $params->get('reply_to_name', ''));

		foreach ([$template, $newsletter] as $layer)
		{
			if ($layer === null || (string) ($layer->reply_to_mode ?? 'inherit') === 'inherit')
			{
				continue;
			}

			$mode = (string) $layer->reply_to_mode;
			$email = trim((string) ($layer->reply_to_email ?? ''));
			$name = trim((string) ($layer->reply_to_name ?? ''));
		}

		if ($mode !== 'custom')
		{
			return ['email' => '', 'name' => ''];
		}

		return ['email' => $email, 'name' => $name];
	}

	/** @return array{email:string,name:string} */
	public function sender(): array
	{
		$params = ComponentHelper::getParams('com_pungamail');
		$email = trim((string) $params->get('from_email', ''));
		$name = trim((string) $params->get('from_name', ''));

		if ($email === '')
		{
			$email = trim((string) Factory::getConfig()->get('mailfrom', ''));
		}

		if ($name === '')
		{
			$name = trim((string) Factory::getConfig()->get('fromname', ''));
		}

		return ['email' => $email, 'name' => $name];
	}

	/** @param array<int,object> $topics @return string */
	public function listId(array $topics): string
	{
		$host = strtolower((string) Uri::getInstance(Uri::root())->getHost());
		$host = preg_replace('/[^a-z0-9.-]/', '', $host) ?: 'localhost';
		$aliases = [];

		foreach ($topics as $topic)
		{
			$alias = preg_replace('/[^a-z0-9.-]/', '-', strtolower((string) ($topic->alias ?? '')));

			if ($alias !== '')
			{
				$aliases[] = $alias;
			}
		}

		sort($aliases, SORT_STRING);
		$aliases = array_values(array_unique($aliases));

		if (count($aliases) === 1)
		{
			$local = $aliases[0];
		}
		elseif (count($aliases) > 1)
		{
			$local = 'topics-' . substr(hash('sha256', implode('|', $aliases)), 0, 16);
		}
		else
		{
			$local = 'pungamail';
		}

		return '<' . $local . '.' . $host . '>';
	}

	/** @return string */
	public function envelopeSender(int $queueId): string
	{
		$settings = $this->mailSettings->getPublic();
		$address = trim((string) ($settings->bounce_address ?? ''));

		if (!filter_var($address, FILTER_VALIDATE_EMAIL))
		{
			return '';
		}

		[$local, $domain] = explode('@', $address, 2);
		$local = preg_replace('/\+.*$/', '', $local) ?: $local;

		return $local . '+pungamail-q' . $queueId . '@' . $domain;
	}

	/** @return bool */
	public function globallyPaused(): bool
	{
		return (int) ComponentHelper::getParams('com_pungamail')->get('queue_paused', 0) === 1;
	}
}
