<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Statistics\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;

$esc = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$number = static fn (int $value): string => number_format($value, 0, ',', '.');
$percent = static function (int $part, int $whole): string {
	if ($whole <= 0)
	{
		return '—';
	}
	return number_format(($part / $whole) * 100, 1, ',', '.') . ' %';
};
$dailyBars = static function (array $rows) use ($esc, $number): string {
	if ($rows === [])
	{
		return '<div class="text-muted">' . $esc(Text::_('COM_PUNGAMAIL_STATISTICS_NO_CLICKS')) . '</div>';
	}
	$max = max(1, ...array_map(static fn (array $row): int => (int) $row['clicks'] + (int) $row['automated_clicks'], $rows));
	$out = '<div class="d-grid gap-2">';
	foreach ($rows as $row)
	{
		$human = (int) $row['clicks'];
		$automated = (int) $row['automated_clicks'];
		$total = $human + $automated;
		$width = max(2, (int) round(($total / $max) * 100));
		$out .= '<div class="d-flex align-items-center gap-3">'
			. '<div class="small text-nowrap" style="width:7.5rem">' . $esc((string) $row['date']) . '</div>'
			. '<div class="progress flex-grow-1" style="height:.75rem"><div class="progress-bar" role="progressbar" style="width:' . $width . '%" aria-valuenow="' . $total . '" aria-valuemin="0" aria-valuemax="' . $max . '"></div></div>'
			. '<div class="small text-nowrap" style="min-width:9rem">' . $number($human) . ' ' . $esc(Text::_('COM_PUNGAMAIL_STATISTICS_CLICKS_SHORT'));
		if ($automated > 0)
		{
			$out .= ' · ' . $number($automated) . ' ' . $esc(Text::_('COM_PUNGAMAIL_STATISTICS_AUTOMATED_SHORT'));
		}
		$out .= '</div></div>';
	}
	return $out . '</div>';
};
?>
<div class="container-fluid">
	<div class="alert alert-info">
		<strong><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_PRIVACY_TITLE'); ?></strong>
		<?php echo Text::_('COM_PUNGAMAIL_STATISTICS_PRIVACY_DESC'); ?>
	</div>

<?php if ($this->report !== null) : ?>
	<?php
	$newsletter = $this->report['newsletter'];
	$delivery = $this->report['delivery'];
	$clicks = $this->report['clicks'];
	$delivered = (int) ($delivery['transport_accepted'] ?? 0);
	?>
	<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
		<div>
			<a class="btn btn-outline-secondary btn-sm mb-2" href="<?php echo Route::_(AdministratorRoute::statistics()); ?>">← <?php echo Text::_('COM_PUNGAMAIL_STATISTICS_ALL_NEWSLETTERS'); ?></a>
			<h2 class="h4 mb-0"><?php echo $esc((string) $newsletter->title); ?></h2>
			<div class="text-muted"><?php echo $esc((string) $newsletter->subject); ?></div>
		</div>
		<?php if (!empty($newsletter->sent_at)) : ?><div class="text-muted"><?php echo HTMLHelper::_('date', (string) $newsletter->sent_at, Text::_('DATE_FORMAT_LC5'), $this->siteTimezone); ?></div><?php endif; ?>
	</div>

	<div class="row g-3 mb-4">
		<?php
		$cards = [
			[Text::_('COM_PUNGAMAIL_STAT_TRANSPORT_ACCEPTED'), $delivered, $percent($delivered, (int) ($delivery['intended'] ?? 0))],
			[Text::_('COM_PUNGAMAIL_STATISTICS_TRUSTED_CLICKS'), (int) $clicks['clicks'], Text::sprintf('COM_PUNGAMAIL_STATISTICS_CLICKS_PER_DELIVERED', $percent((int) $clicks['clicks'], $delivered))],
			[Text::_('COM_PUNGAMAIL_STATISTICS_LINKS_CLICKED'), (int) $clicks['links_clicked'], ''],
			[Text::_('COM_PUNGAMAIL_STAT_UNSUBSCRIBES'), (int) ($delivery['unsubscribes'] ?? 0), $percent((int) ($delivery['unsubscribes'] ?? 0), $delivered)],
			[Text::_('COM_PUNGAMAIL_STAT_HARD_BOUNCES'), (int) ($delivery['hard_bounces'] ?? 0), ''],
			[Text::_('COM_PUNGAMAIL_STAT_SOFT_BOUNCES'), (int) ($delivery['soft_bounces'] ?? 0), ''],
			[Text::_('COM_PUNGAMAIL_STATISTICS_AUTOMATED_CLICKS'), (int) $clicks['automated_clicks'], Text::_('COM_PUNGAMAIL_STATISTICS_EXCLUDED_FROM_CLICKS')],
		];
		foreach ($cards as [$label, $value, $hint]) : ?>
		<div class="col-6 col-md-4 col-xl-3"><div class="card h-100"><div class="card-body">
			<div class="small text-muted"><?php echo $esc($label); ?></div>
			<div class="fs-3"><?php echo $number((int) $value); ?></div>
			<?php if ($hint !== '') : ?><div class="small text-muted"><?php echo $esc($hint); ?></div><?php endif; ?>
		</div></div></div>
		<?php endforeach; ?>
	</div>

	<div class="row g-3 mb-4">
		<div class="col-12 col-xl-5"><div class="card h-100"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_CLICK_TIMELINE'); ?></strong></div><div class="card-body"><?php echo $dailyBars($this->report['daily_clicks']); ?></div></div></div>
		<div class="col-12 col-xl-7"><div class="card h-100"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_LINK_PERFORMANCE'); ?></strong></div><div class="card-body p-0">
			<div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>#</th><th><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_DESTINATION'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_CONTENT_LABEL'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_TRUSTED_CLICKS'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_AUTOMATED_CLICKS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_LAST_CLICK'); ?></th></tr></thead><tbody>
			<?php foreach ($this->report['links'] as $link) : ?><tr>
				<td><?php echo (int) $link->link_index; ?></td>
				<td class="text-break"><code><?php echo $esc((string) ($link->destination_path ?: $link->destination_url)); ?></code></td>
				<td><?php echo $esc((string) ($link->utm_content ?: '—')); ?></td>
				<td class="text-end"><?php echo $number((int) $link->clicks); ?></td>
				<td class="text-end"><?php echo $number((int) $link->automated_clicks); ?></td>
				<td><?php echo $link->last_click ? HTMLHelper::_('date', (string) $link->last_click, Text::_('DATE_FORMAT_LC5'), $this->siteTimezone) : '—'; ?></td>
			</tr><?php endforeach; ?>
			<?php if ($this->report['links'] === []) : ?><tr><td colspan="6" class="text-center text-muted py-4"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_NO_CLICKS'); ?></td></tr><?php endif; ?>
			</tbody></table></div>
		</div></div></div>
	</div>

	<div class="card mb-4"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_CLICK_MAP'); ?></strong></div><div class="card-body">
		<p class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_CLICK_MAP_DESC'); ?></p>
		<?php if ((string) $this->report['click_map_html'] !== '') : ?>
			<iframe sandbox="" title="<?php echo $esc(Text::_('COM_PUNGAMAIL_STATISTICS_CLICK_MAP')); ?>" style="width:100%;height:700px;border:1px solid #ccc;background:#fff" srcdoc="<?php echo $esc((string) $this->report['click_map_html']); ?>"></iframe>
		<?php else : ?><div class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_NO_SNAPSHOT'); ?></div><?php endif; ?>
	</div></div>

	<div class="alert alert-secondary small"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_EXTERNAL_NOTE'); ?></div>
<?php else : ?>
	<?php $totals = $this->dashboard['totals'] ?? []; ?>
	<form method="get" action="<?php echo Route::_('index.php'); ?>" class="d-flex flex-wrap gap-2 align-items-end mb-4">
		<input type="hidden" name="option" value="com_pungamail"><input type="hidden" name="view" value="statistics">
		<div><label class="form-label" for="pm-stats-days"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_PERIOD'); ?></label><select class="form-select" id="pm-stats-days" name="days" onchange="this.form.submit()">
		<?php foreach ([7,30,90,365,0] as $days) : ?><option value="<?php echo $days; ?>" <?php echo $this->days === $days ? 'selected' : ''; ?>><?php echo Text::_($days === 0 ? 'COM_PUNGAMAIL_STATISTICS_ALL_TIME' : 'COM_PUNGAMAIL_STATISTICS_LAST_' . $days . '_DAYS'); ?></option><?php endforeach; ?>
		</select></div>
	</form>

	<div class="row g-3 mb-4">
		<?php
		$delivered = (int) ($totals['sent'] ?? 0);
		$cards = [
			[Text::_('COM_PUNGAMAIL_STATISTICS_SENT_NEWSLETTERS'), (int) ($totals['newsletters'] ?? 0), ''],
			[Text::_('COM_PUNGAMAIL_STAT_TRANSPORT_ACCEPTED'), $delivered, $percent($delivered, (int) ($totals['intended'] ?? 0))],
			[Text::_('COM_PUNGAMAIL_STATISTICS_TRUSTED_CLICKS'), (int) ($totals['clicks'] ?? 0), Text::sprintf('COM_PUNGAMAIL_STATISTICS_CLICKS_PER_DELIVERED', $percent((int) ($totals['clicks'] ?? 0), $delivered))],
			[Text::_('COM_PUNGAMAIL_STATISTICS_LINKS_CLICKED'), (int) ($totals['links_clicked'] ?? 0), ''],
			[Text::_('COM_PUNGAMAIL_FAILED'), (int) ($totals['failed'] ?? 0), ''],
			[Text::_('COM_PUNGAMAIL_STATISTICS_BOUNCED'), (int) ($totals['bounced'] ?? 0), ''],
			[Text::_('COM_PUNGAMAIL_STAT_UNSUBSCRIBES'), (int) ($totals['unsubscribes'] ?? 0), $percent((int) ($totals['unsubscribes'] ?? 0), $delivered)],
			[Text::_('COM_PUNGAMAIL_STATISTICS_AUTOMATED_CLICKS'), (int) ($totals['automated_clicks'] ?? 0), Text::_('COM_PUNGAMAIL_STATISTICS_EXCLUDED_FROM_CLICKS')],
		];
		foreach ($cards as [$label, $value, $hint]) : ?>
		<div class="col-6 col-md-4 col-xl-3"><div class="card h-100"><div class="card-body">
			<div class="small text-muted"><?php echo $esc($label); ?></div><div class="fs-3"><?php echo $number((int) $value); ?></div>
			<?php if ($hint !== '') : ?><div class="small text-muted"><?php echo $esc($hint); ?></div><?php endif; ?>
		</div></div></div>
		<?php endforeach; ?>
	</div>

	<div class="card mb-4"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_CLICK_TIMELINE'); ?></strong></div><div class="card-body"><?php echo $dailyBars($this->dashboard['daily_clicks'] ?? []); ?></div></div>

	<?php if (($this->dashboard['campaigns'] ?? []) !== []) : ?>
	<div class="card mb-4"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_CAMPAIGN_SUMMARY'); ?></strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr>
		<th><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_CAMPAIGN'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_SENT_NEWSLETTERS'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_TRUSTED_CLICKS'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_AUTOMATED_CLICKS'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_LINKS_CLICKED'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_LAST_CLICK'); ?></th>
	</tr></thead><tbody>
	<?php foreach ($this->dashboard['campaigns'] as $campaign) : ?><tr>
		<td><code><?php echo $esc((string) $campaign->utm_campaign); ?></code></td><td class="text-end"><?php echo $number((int) $campaign->newsletters); ?></td><td class="text-end"><?php echo $number((int) $campaign->clicks); ?></td><td class="text-end"><?php echo $number((int) $campaign->automated_clicks); ?></td><td class="text-end"><?php echo $number((int) $campaign->links_clicked); ?></td><td><?php echo $campaign->last_click ? HTMLHelper::_('date', (string) $campaign->last_click, Text::_('DATE_FORMAT_LC5'), $this->siteTimezone) : '—'; ?></td>
	</tr><?php endforeach; ?>
	</tbody></table></div></div></div>
	<?php endif; ?>

	<div class="card"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_NEWSLETTER_REPORTS'); ?></strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr>
		<th><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTERS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SENT_AT'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_CAMPAIGN'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_SENT'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_STAT_UNSUBSCRIBES'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_TRUSTED_CLICKS'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_AUTOMATED_CLICKS'); ?></th><th></th>
	</tr></thead><tbody>
	<?php foreach ($this->dashboard['newsletters'] ?? [] as $row) : ?><tr>
		<td><strong><?php echo $esc((string) $row->title); ?></strong><div class="small text-muted"><?php echo $esc((string) $row->subject); ?></div></td>
		<td><?php echo $row->sent_at ? HTMLHelper::_('date', (string) $row->sent_at, Text::_('DATE_FORMAT_LC5'), $this->siteTimezone) : '—'; ?></td>
		<td><?php echo $esc((string) ($row->campaign ?: '—')); ?></td>
		<td class="text-end"><?php echo $number((int) $row->recipient_count); ?></td><td class="text-end"><?php echo $number((int) $row->sent_count); ?></td><td class="text-end"><?php echo $number((int) $row->unsubscribes); ?></td><td class="text-end"><?php echo $number((int) $row->clicks); ?></td><td class="text-end"><?php echo $number((int) $row->automated_clicks); ?></td>
		<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_(AdministratorRoute::statistics((int) $row->id)); ?>"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_VIEW_REPORT'); ?></a></td>
	</tr><?php endforeach; ?>
	<?php if (($this->dashboard['newsletters'] ?? []) === []) : ?><tr><td colspan="9" class="text-center text-muted py-4"><?php echo Text::_('COM_PUNGAMAIL_STATISTICS_NO_NEWSLETTERS'); ?></td></tr><?php endif; ?>
	</tbody></table></div></div></div>
<?php endif; ?>
</div>
