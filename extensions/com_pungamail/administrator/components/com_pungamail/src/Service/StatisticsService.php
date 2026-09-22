<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Date\Date;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Computes transport and privacy-conscious campaign statistics. */
final class StatisticsService
{
	/** @param DatabaseInterface $db Database connection. */
	public function __construct(
		private readonly DatabaseInterface $db,
		private readonly ?TokenService $tokens = null
	)
	{
	}

	/** @return array<string,int> */
	public function forNewsletter(object $newsletter): array
	{
		$newsletterId = (int) $newsletter->id;
		$query = $this->db->getQuery(true)
			->select([
				'COUNT(*) AS queued',
				"SUM(CASE WHEN status IN ('sent', 'bounced') THEN 1 ELSE 0 END) AS transport_accepted",
				"SUM(CASE WHEN status = 'pending' AND attempts > 0 THEN 1 ELSE 0 END) AS temporary_failures",
				"SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS permanent_failures",
				"SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) AS remaining",
				"SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled",
			])
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$queue = $this->db->setQuery($query)->loadObject();
		$hard = 'hard';
		$soft = 'soft';
		$bounceQuery = $this->db->getQuery(true)
			->select([
				'SUM(CASE WHEN classification = :hard THEN 1 ELSE 0 END) AS hard_bounces',
				'SUM(CASE WHEN classification = :soft THEN 1 ELSE 0 END) AS soft_bounces',
			])
			->from($this->db->quoteName('#__pungamail_bounces'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->bind(':hard', $hard)
			->bind(':soft', $soft)
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$bounces = $this->db->setQuery($bounceQuery)->loadObject();
		$eventType = 'unsubscribe_completed';
		$oneClick = 'one_click_unsubscribe';
		$unsubscribeQuery = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__pungamail_events'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->whereIn($this->db->quoteName('event_type'), [$eventType, $oneClick], ParameterType::STRING)
			->bind(':id', $newsletterId, ParameterType::INTEGER);

		return [
			'intended' => (int) ($newsletter->intended_count ?? $newsletter->recipient_count ?? 0),
			'queued' => (int) ($queue->queued ?? 0),
			'transport_accepted' => (int) ($queue->transport_accepted ?? 0),
			'temporary_failures' => (int) ($queue->temporary_failures ?? 0),
			'permanent_failures' => (int) ($queue->permanent_failures ?? 0),
			'hard_bounces' => (int) ($bounces->hard_bounces ?? 0),
			'soft_bounces' => (int) ($bounces->soft_bounces ?? 0),
			'suppressed' => (int) ($newsletter->suppressed_count ?? 0),
			'unsubscribes' => (int) $this->db->setQuery($unsubscribeQuery)->loadResult(),
			'remaining' => (int) ($queue->remaining ?? 0),
			'cancelled' => (int) ($queue->cancelled ?? 0),
		];
	}

	/**
	 * Records one trusted internal campaign visit. No recipient identifier, IP
	 * address, or raw user agent is stored. A small user-agent heuristic only
	 * records whether the request looks automated so reports can separate likely
	 * mail-security scanners from ordinary clicks.
	 *
	 * @param array<string,mixed> $visit Validated signed campaign data.
	 * @param string              $userAgent Current request user agent.
	 *
	 * @return void
	 */
	public function recordCampaignClick(array $visit, string $userAgent): void
	{
		$newsletterId = max(0, (int) ($visit['newsletter_id'] ?? 0));
		$linkIndex = max(0, (int) ($visit['link_index'] ?? 0));

		if ($newsletterId <= 0 || $linkIndex <= 0)
		{
			return;
		}

		$row = (object) [
			'newsletter_id' => $newsletterId,
			'link_index' => $linkIndex,
			'destination_url' => mb_substr((string) ($visit['url'] ?? ''), 0, 8192),
			'destination_path' => mb_substr((string) ($visit['path'] ?? ''), 0, 2048),
			'utm_source' => mb_substr((string) ($visit['utm_source'] ?? ''), 0, 255),
			'utm_medium' => mb_substr((string) ($visit['utm_medium'] ?? ''), 0, 255),
			'utm_campaign' => mb_substr((string) ($visit['utm_campaign'] ?? ''), 0, 255),
			'utm_id' => mb_substr((string) ($visit['utm_id'] ?? ''), 0, 255),
			'utm_content' => mb_substr((string) ($visit['utm_content'] ?? ''), 0, 255),
			'is_automated' => $this->looksAutomated($userAgent) ? 1 : 0,
			'clicked_at' => (new Date('now', 'UTC'))->toSql(),
		];

		$this->db->insertObject('#__pungamail_campaign_clicks', $row);
	}

	/**
	 * Returns the global campaign-statistics dashboard for the requested period.
	 *
	 * @param int $days Number of days; zero means all history.
	 *
	 * @return array<string,mixed>
	 */
	public function campaignDashboard(int $days = 90): array
	{
		$days = in_array($days, [0, 7, 30, 90, 365], true) ? $days : 90;
		$cutoff = $this->periodCutoff($days);
		$where = $this->sentPeriodWhere($cutoff);
		$query = $this->db->getQuery(true)
			->select([
				'COUNT(*) AS newsletters',
				'COALESCE(SUM(' . $this->db->quoteName('intended_count') . '), 0) AS intended',
				'COALESCE(SUM(' . $this->db->quoteName('sent_count') . '), 0) AS sent',
				'COALESCE(SUM(' . $this->db->quoteName('failed_count') . '), 0) AS failed',
				'COALESCE(SUM(' . $this->db->quoteName('bounced_count') . '), 0) AS bounced',
			])
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->where($this->db->quoteName('sent_at') . ' IS NOT NULL');
		if ($where !== null)
		{
			$query->where($where)->bind(':statsCutoff', $cutoff);
		}
		$totals = $this->db->setQuery($query)->loadObject() ?: (object) [];
		$clickTotals = $this->campaignClickTotals(0, $cutoff);
		$unsubscribes = $this->unsubscribeCount($cutoff);

		return [
			'days' => $days,
			'cutoff' => $cutoff,
			'totals' => [
				'newsletters' => (int) ($totals->newsletters ?? 0),
				'intended' => (int) ($totals->intended ?? 0),
				'sent' => (int) ($totals->sent ?? 0),
				'failed' => (int) ($totals->failed ?? 0),
				'bounced' => (int) ($totals->bounced ?? 0),
				'unsubscribes' => $unsubscribes,
				'clicks' => $clickTotals['clicks'],
				'automated_clicks' => $clickTotals['automated_clicks'],
				'links_clicked' => $clickTotals['links_clicked'],
			],
			'campaigns' => $this->campaignGroups($cutoff),
			'newsletters' => $this->campaignNewsletterRows($cutoff, 100),
			'daily_clicks' => $this->dailyClicks(0, $cutoff),
		];
	}

	/**
	 * Returns detailed statistics for one sent newsletter.
	 *
	 * @return array<string,mixed>|null
	 */
	public function campaignReport(int $newsletterId): ?array
	{
		if ($newsletterId <= 0)
		{
			return null;
		}

		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		$newsletter = $this->db->setQuery($query)->loadObject();

		if ($newsletter === null)
		{
			return null;
		}

		$links = $this->linkPerformance($newsletterId);

		return [
			'newsletter' => $newsletter,
			'delivery' => $this->forNewsletter($newsletter),
			'clicks' => $this->campaignClickTotals($newsletterId, null),
			'links' => $links,
			'daily_clicks' => $this->dailyClicks($newsletterId, null),
			'click_map_html' => $this->clickMapHtml($newsletter, $links),
		];
	}

	/** @return array{clicks:int,automated_clicks:int,links_clicked:int,last_click:?string} */
	private function campaignClickTotals(int $newsletterId, ?string $cutoff): array
	{
		$query = $this->db->getQuery(true)
			->select([
				"COALESCE(SUM(CASE WHEN is_automated = 0 THEN 1 ELSE 0 END), 0) AS clicks",
				"COALESCE(SUM(CASE WHEN is_automated = 1 THEN 1 ELSE 0 END), 0) AS automated_clicks",
				"COUNT(DISTINCT CASE WHEN is_automated = 0 THEN CONCAT(newsletter_id, ':', link_index) END) AS links_clicked",
				'MAX(' . $this->db->quoteName('clicked_at') . ') AS last_click',
			])
			->from($this->db->quoteName('#__pungamail_campaign_clicks'));

		if ($newsletterId > 0)
		{
			$query->where($this->db->quoteName('newsletter_id') . ' = :newsletterId')
				->bind(':newsletterId', $newsletterId, ParameterType::INTEGER);
		}
		if ($cutoff !== null)
		{
			$query->where($this->db->quoteName('clicked_at') . ' >= :clickCutoff')
				->bind(':clickCutoff', $cutoff);
		}
		$row = $this->db->setQuery($query)->loadObject() ?: (object) [];

		return [
			'clicks' => (int) ($row->clicks ?? 0),
			'automated_clicks' => (int) ($row->automated_clicks ?? 0),
			'links_clicked' => (int) ($row->links_clicked ?? 0),
			'last_click' => isset($row->last_click) && $row->last_click !== null ? (string) $row->last_click : null,
		];
	}

	/** @return array<int,object> */
	private function campaignGroups(?string $cutoff): array
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('utm_campaign'),
				'COUNT(DISTINCT ' . $this->db->quoteName('newsletter_id') . ') AS newsletters',
				"SUM(CASE WHEN is_automated = 0 THEN 1 ELSE 0 END) AS clicks",
				"SUM(CASE WHEN is_automated = 1 THEN 1 ELSE 0 END) AS automated_clicks",
				"COUNT(DISTINCT CASE WHEN is_automated = 0 THEN CONCAT(newsletter_id, ':', link_index) END) AS links_clicked",
				'MAX(' . $this->db->quoteName('clicked_at') . ') AS last_click',
			])
			->from($this->db->quoteName('#__pungamail_campaign_clicks'))
			->where($this->db->quoteName('utm_campaign') . " <> ''")
			->group($this->db->quoteName('utm_campaign'))
			->order('clicks DESC, ' . $this->db->quoteName('utm_campaign') . ' ASC');
		if ($cutoff !== null)
		{
			$query->where($this->db->quoteName('clicked_at') . ' >= :campaignCutoff')->bind(':campaignCutoff', $cutoff);
		}
		return $this->db->setQuery($query, 0, 100)->loadObjectList();
	}

	/** @return array<int,object> */
	private function campaignNewsletterRows(?string $cutoff, int $limit): array
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('id'),
				$this->db->quoteName('title'),
				$this->db->quoteName('subject'),
				$this->db->quoteName('sent_at'),
				$this->db->quoteName('recipient_count'),
				$this->db->quoteName('sent_count'),
				$this->db->quoteName('failed_count'),
				$this->db->quoteName('bounced_count'),
				$this->db->quoteName('utm_campaign'),
			])
			->from($this->db->quoteName('#__pungamail_newsletters'))
			->where($this->db->quoteName('sent_at') . ' IS NOT NULL')
			->order($this->db->quoteName('sent_at') . ' DESC');
		if ($cutoff !== null)
		{
			$query->where($this->db->quoteName('sent_at') . ' >= :newsletterCutoff')
				->bind(':newsletterCutoff', $cutoff);
		}
		$rows = $this->db->setQuery($query, 0, max(1, min(500, $limit)))->loadObjectList();
		if ($rows === [])
		{
			return [];
		}

		$ids = array_map(static fn (object $row): int => (int) $row->id, $rows);
		$clickMap = $this->bulkClickSummaries($ids);
		$unsubscribeMap = $this->bulkUnsubscribes($ids);

		foreach ($rows as $row)
		{
			$id = (int) $row->id;
			$click = $clickMap[$id] ?? ['clicks' => 0, 'automated_clicks' => 0, 'links_clicked' => 0, 'campaign' => ''];
			$row->clicks = (int) $click['clicks'];
			$row->automated_clicks = (int) $click['automated_clicks'];
			$row->links_clicked = (int) $click['links_clicked'];
			$row->campaign = trim((string) $click['campaign']);
			if ($row->campaign === '')
			{
				$row->campaign = trim((string) ($row->utm_campaign ?? ''));
			}
			$row->unsubscribes = (int) ($unsubscribeMap[$id] ?? 0);
		}

		return $rows;
	}

	/** @param array<int,int> $ids @return array<int,array<string,int|string>> */
	private function bulkClickSummaries(array $ids): array
	{
		if ($ids === [])
		{
			return [];
		}

		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('newsletter_id'),
				"SUM(CASE WHEN is_automated = 0 THEN 1 ELSE 0 END) AS clicks",
				"SUM(CASE WHEN is_automated = 1 THEN 1 ELSE 0 END) AS automated_clicks",
				"COUNT(DISTINCT CASE WHEN is_automated = 0 THEN link_index END) AS links_clicked",
				'MAX(' . $this->db->quoteName('utm_campaign') . ') AS campaign',
			])
			->from($this->db->quoteName('#__pungamail_campaign_clicks'))
			->whereIn($this->db->quoteName('newsletter_id'), $ids)
			->group($this->db->quoteName('newsletter_id'));
		$result = [];
		foreach ($this->db->setQuery($query)->loadObjectList() as $row)
		{
			$result[(int) $row->newsletter_id] = [
				'clicks' => (int) $row->clicks,
				'automated_clicks' => (int) $row->automated_clicks,
				'links_clicked' => (int) $row->links_clicked,
				'campaign' => (string) $row->campaign,
			];
		}
		return $result;
	}

	/** @param array<int,int> $ids @return array<int,int> */
	private function bulkUnsubscribes(array $ids): array
	{
		if ($ids === [])
		{
			return [];
		}
		$query = $this->db->getQuery(true)
			->select([$this->db->quoteName('newsletter_id'), 'COUNT(*) AS total'])
			->from($this->db->quoteName('#__pungamail_events'))
			->whereIn($this->db->quoteName('newsletter_id'), $ids)
			->whereIn($this->db->quoteName('event_type'), ['unsubscribe_completed', 'one_click_unsubscribe'], ParameterType::STRING)
			->group($this->db->quoteName('newsletter_id'));
		$result = [];
		foreach ($this->db->setQuery($query)->loadObjectList() as $row)
		{
			$result[(int) $row->newsletter_id] = (int) $row->total;
		}
		return $result;
	}

	/** @return int */
	private function unsubscribeCount(?string $cutoff): int
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__pungamail_events'))
			->whereIn($this->db->quoteName('event_type'), ['unsubscribe_completed', 'one_click_unsubscribe'], ParameterType::STRING);
		if ($cutoff !== null)
		{
			$query->where($this->db->quoteName('created') . ' >= :eventCutoff')->bind(':eventCutoff', $cutoff);
		}
		return (int) $this->db->setQuery($query)->loadResult();
	}

	/** @return array<int,object> */
	private function linkPerformance(int $newsletterId): array
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('link_index'),
				'MAX(' . $this->db->quoteName('destination_url') . ') AS destination_url',
				'MAX(' . $this->db->quoteName('destination_path') . ') AS destination_path',
				'MAX(' . $this->db->quoteName('utm_content') . ') AS utm_content',
				'MAX(' . $this->db->quoteName('utm_campaign') . ') AS utm_campaign',
				"SUM(CASE WHEN is_automated = 0 THEN 1 ELSE 0 END) AS clicks",
				"SUM(CASE WHEN is_automated = 1 THEN 1 ELSE 0 END) AS automated_clicks",
				'MIN(' . $this->db->quoteName('clicked_at') . ') AS first_click',
				'MAX(' . $this->db->quoteName('clicked_at') . ') AS last_click',
			])
			->from($this->db->quoteName('#__pungamail_campaign_clicks'))
			->where($this->db->quoteName('newsletter_id') . ' = :id')
			->group($this->db->quoteName('link_index'))
			->order('clicks DESC, ' . $this->db->quoteName('link_index') . ' ASC')
			->bind(':id', $newsletterId, ParameterType::INTEGER);
		return $this->db->setQuery($query)->loadObjectList();
	}

	/** @return array<int,array{date:string,clicks:int,automated_clicks:int}> */
	private function dailyClicks(int $newsletterId, ?string $cutoff): array
	{
		$query = $this->db->getQuery(true)
			->select([
				'DATE(' . $this->db->quoteName('clicked_at') . ') AS click_date',
				"SUM(CASE WHEN is_automated = 0 THEN 1 ELSE 0 END) AS clicks",
				"SUM(CASE WHEN is_automated = 1 THEN 1 ELSE 0 END) AS automated_clicks",
			])
			->from($this->db->quoteName('#__pungamail_campaign_clicks'))
			->group('DATE(' . $this->db->quoteName('clicked_at') . ')')
			->order('click_date ASC');
		if ($newsletterId > 0)
		{
			$query->where($this->db->quoteName('newsletter_id') . ' = :dailyNewsletterId')
				->bind(':dailyNewsletterId', $newsletterId, ParameterType::INTEGER);
		}
		if ($cutoff !== null)
		{
			$query->where($this->db->quoteName('clicked_at') . ' >= :dailyCutoff')->bind(':dailyCutoff', $cutoff);
		}
		$result = [];
		foreach ($this->db->setQuery($query)->loadObjectList() as $row)
		{
			$result[] = ['date' => (string) $row->click_date, 'clicks' => (int) $row->clicks, 'automated_clicks' => (int) $row->automated_clicks];
		}
		return $result;
	}

	/** @return string|null */
	private function periodCutoff(int $days): ?string
	{
		if ($days <= 0)
		{
			return null;
		}
		$date = new Date('now', 'UTC');
		$date->modify('-' . $days . ' days');
		return $date->toSql();
	}

	/** @return string|null */
	private function sentPeriodWhere(?string $cutoff): ?string
	{
		return $cutoff !== null ? $this->db->quoteName('sent_at') . ' >= :statsCutoff' : null;
	}

	/** @return bool */
	private function looksAutomated(string $userAgent): bool
	{
		$userAgent = strtolower(trim($userAgent));
		if ($userAgent === '')
		{
			return false;
		}
		return preg_match('/(?:bot|crawler|spider|scanner|urlscan|proofpoint|mimecast|barracuda|safelinks|messagelabs|symantec|sophos|fortinet|fireeye|zscaler|trendmicro|linkcheck)/i', $userAgent) === 1;
	}

	/**
	 * Returns the frozen sent HTML with non-clickable per-link click badges.
	 *
	 * @param object            $newsletter Sent newsletter.
	 * @param array<int,object> $links      Aggregated link statistics.
	 *
	 * @return string
	 */
	private function clickMapHtml(object $newsletter, array $links): string
	{
		$html = (string) ($newsletter->snapshot_html ?? '');
		if ($html === '' || $this->tokens === null || !class_exists('DOMDocument'))
		{
			return $html;
		}

		$byIndex = [];
		foreach ($links as $link)
		{
			$byIndex[(int) $link->link_index] = $link;
		}

		$document = new \DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(true);
		$loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		if (!$loaded)
		{
			return $html;
		}

		$anchors = [];
		foreach ($document->getElementsByTagName('a') as $anchor)
		{
			$anchors[] = $anchor;
		}

		foreach ($anchors as $anchor)
		{
			$href = html_entity_decode((string) $anchor->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
			$query = parse_url($href, PHP_URL_QUERY);
			if (!is_string($query) || $query === '')
			{
				continue;
			}
			parse_str($query, $vars);
			$token = trim((string) ($vars['pm_track'] ?? ''));
			if ($token === '')
			{
				continue;
			}
			$data = $this->tokens->validateCampaignToken($token);
			if ($data === null || (int) ($data['newsletter_id'] ?? 0) !== (int) $newsletter->id)
			{
				continue;
			}
			$index = (int) ($data['link_index'] ?? 0);
			$link = $byIndex[$index] ?? null;
			$clicks = (int) ($link->clicks ?? 0);
			$automated = (int) ($link->automated_clicks ?? 0);
			$badge = $document->createElement('span', $clicks . ' click' . ($clicks === 1 ? '' : 's'));
			$badge->setAttribute('style', 'display:inline-block;margin:2px 4px;padding:2px 5px;border:1px solid #6c757d;border-radius:4px;background:#fff;color:#212529;font:11px/1.2 sans-serif;white-space:nowrap;');
			if ($automated > 0)
			{
				$badge->setAttribute('title', $automated . ' likely automated click(s) excluded');
			}
			$anchor->removeAttribute('href');
			$anchor->setAttribute('aria-disabled', 'true');
			if ($anchor->parentNode !== null)
			{
				$anchor->parentNode->insertBefore($badge, $anchor->nextSibling);
			}
		}

		$output = $document->saveHTML();
		return is_string($output) ? preg_replace('/^<\?xml[^>]+>/', '', $output) ?? $output : $html;
	}
}
