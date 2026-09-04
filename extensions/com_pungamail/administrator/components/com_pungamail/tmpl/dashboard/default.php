<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Dashboard\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;

$data = $this->data;
$subscribers = $data['subscribers'];
$queue = $data['queue'];
$tasks = $data['tasks'];
$automationNeeds = $data['automation'];
$overview = (array) ($data['overview'] ?? []);
$lastNewsletter = $overview['last_newsletter'] ?? null;
$nextAutomatic = $overview['next_automatic'] ?? null;
$delivery = (array) ($overview['delivery'] ?? ['sent' => 0, 'failed' => 0, 'bounced' => 0, 'rate' => null]);
$chart = (array) ($overview['chart'] ?? []);
$upcoming = (array) ($overview['upcoming'] ?? []);
$automatic = (array) ($overview['automatic'] ?? []);
$channelStats = (array) ($overview['channel_stats'] ?? []);
$activity = (array) ($overview['activity'] ?? []);
$taskReady = static fn (?object $task): bool => $task !== null && (int) $task->state === 1;
$issues = [];

if ((bool) ($data['schema_incomplete'] ?? false))
{
	$issues[] = ['class' => 'danger', 'text' => Text::_('COM_PUNGAMAIL_DATABASE_UPDATE_REQUIRED'), 'url' => 'index.php?option=com_installer&view=database'];
}

if (!$taskReady($tasks['queue']))
{
	$issues[] = ['class' => 'warning', 'text' => Text::_('COM_PUNGAMAIL_TASK_NOT_CONFIGURED'), 'url' => 'index.php?option=com_scheduler&view=tasks'];
}

if ((int) ($automationNeeds['digests'] ?? 0) > 0 && !$taskReady($tasks['digests']))
{
	$issues[] = ['class' => 'warning', 'text' => Text::_('COM_PUNGAMAIL_DIGEST_TASK_NOT_CONFIGURED'), 'url' => 'index.php?option=com_scheduler&view=tasks'];
}

if ((int) ($automationNeeds['scheduled'] ?? 0) > 0 && !$taskReady($tasks['scheduled']))
{
	$issues[] = ['class' => 'warning', 'text' => Text::_('COM_PUNGAMAIL_SCHEDULED_SEND_TASK_NOT_CONFIGURED'), 'url' => 'index.php?option=com_scheduler&view=tasks'];
}

if (($automationNeeds['bounce_configured'] ?? false) && !$taskReady($tasks['bounces']))
{
	$issues[] = ['class' => 'warning', 'text' => Text::_('COM_PUNGAMAIL_BOUNCE_TASK_NOT_CONFIGURED'), 'url' => 'index.php?option=com_scheduler&view=tasks'];
}

if ((int) ($queue['failed'] ?? 0) > 0)
{
	$issues[] = ['class' => 'warning', 'text' => Text::sprintf('COM_PUNGAMAIL_DASHBOARD_FAILED_DELIVERIES', (int) $queue['failed']), 'url' => 'index.php?option=com_pungamail&view=delivery'];
}

$chartMax = 1;
foreach ($chart as $point)
{
	$chartMax = max($chartMax, (int) ($point['sent'] ?? 0) + (int) ($point['failed'] ?? 0));
}
?>
<style>
.pm-dashboard-card-link { color: inherit; text-decoration: none; }
.pm-dashboard-card-link:hover .card { border-color: var(--template-link-color, #0d6efd); }
.pm-dashboard-kicker { font-size: .78rem; letter-spacing: .02em; color: var(--secondary-color, #6c757d); }
.pm-dashboard-value { font-size: 2rem; line-height: 1.05; font-weight: 600; }
.pm-dashboard-chart { height: 150px; display: flex; gap: 3px; align-items: end; padding-top: 1rem; }
.pm-dashboard-bar-wrap { flex: 1 1 0; height: 100%; display: flex; align-items: end; min-width: 3px; }
.pm-dashboard-bar { width: 100%; min-height: 2px; background: var(--template-link-color, #0d6efd); border-radius: 2px 2px 0 0; opacity: .82; }
.pm-dashboard-activity { position: relative; padding-left: 1.15rem; }
.pm-dashboard-activity::before { content: ''; position: absolute; left: .2rem; top: .5rem; bottom: -.8rem; width: 1px; background: var(--border-color, #d9d9d9); }
.pm-dashboard-activity:last-child::before { display: none; }
.pm-dashboard-activity::after { content: ''; position: absolute; left: 0; top: .48rem; width: .45rem; height: .45rem; border-radius: 50%; background: var(--template-link-color, #0d6efd); }
</style>
<div class="container-fluid">
	<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
		<div>
			<h2 class="h4 mb-1"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_OVERVIEW'); ?></h2>
			<div class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_OVERVIEW_DESC'); ?></div>
		</div>
		<div class="small text-muted">Punga Mail <?php echo htmlspecialchars((string) $data['version'], ENT_QUOTES, 'UTF-8'); ?></div>
	</div>

	<div class="row g-3 mb-4">
		<div class="col-12 col-sm-6 col-xl-3">
			<a class="pm-dashboard-card-link" href="<?php echo Route::_(AdministratorRoute::subscribers()); ?>">
				<div class="card h-100"><div class="card-body">
					<div class="pm-dashboard-kicker mb-2"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_ACTIVE_RECIPIENTS'); ?></div>
					<div class="pm-dashboard-value"><?php echo (int) $subscribers['subscribed']; ?></div>
					<div class="small text-muted mt-2"><?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_CHANNEL_COUNT', (int) ($overview['channels'] ?? 0)); ?></div>
				</div></div>
			</a>
		</div>
		<div class="col-12 col-sm-6 col-xl-3">
			<a class="pm-dashboard-card-link" href="<?php echo $lastNewsletter ? Route::_(AdministratorRoute::newsletter((int) $lastNewsletter->id)) : Route::_(AdministratorRoute::newsletters()); ?>">
				<div class="card h-100"><div class="card-body">
					<div class="pm-dashboard-kicker mb-2"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_LAST_NEWSLETTER'); ?></div>
					<?php if ($lastNewsletter) : ?>
						<div class="fw-semibold text-truncate"><?php echo htmlspecialchars((string) $lastNewsletter->title, ENT_QUOTES, 'UTF-8'); ?></div>
						<div class="small text-muted mt-2"><?php echo HTMLHelper::_('date', $lastNewsletter->sent_at, Text::_('DATE_FORMAT_LC5'), 'UTC'); ?> · <?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_RECIPIENT_COUNT', (int) $lastNewsletter->recipient_count); ?></div>
					<?php else : ?>
						<div class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_NOT_SENT_YET'); ?></div>
					<?php endif; ?>
				</div></div>
			</a>
		</div>
		<div class="col-12 col-sm-6 col-xl-3">
			<a class="pm-dashboard-card-link" href="<?php echo $nextAutomatic ? Route::_(AdministratorRoute::digest((int) $nextAutomatic->id)) : Route::_(AdministratorRoute::digests()); ?>">
				<div class="card h-100"><div class="card-body">
					<div class="pm-dashboard-kicker mb-2"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_NEXT_AUTOMATIC'); ?></div>
					<?php if ($nextAutomatic) : ?>
						<div class="fw-semibold text-truncate"><?php echo htmlspecialchars((string) $nextAutomatic->title, ENT_QUOTES, 'UTF-8'); ?></div>
						<div class="small text-muted mt-2"><?php echo HTMLHelper::_('date', $nextAutomatic->next_run_at, Text::_('DATE_FORMAT_LC5'), 'UTC'); ?></div>
					<?php else : ?>
						<div class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_NO_AUTOMATIC'); ?></div>
					<?php endif; ?>
				</div></div>
			</a>
		</div>
		<div class="col-12 col-sm-6 col-xl-3">
			<a class="pm-dashboard-card-link" href="<?php echo Route::_('index.php?option=com_pungamail&view=delivery'); ?>">
				<div class="card h-100"><div class="card-body">
					<div class="pm-dashboard-kicker mb-2"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_DELIVERY_HEALTH'); ?></div>
					<div class="pm-dashboard-value"><?php echo $delivery['rate'] !== null ? number_format((float) $delivery['rate'], 1) . '%' : '—'; ?></div>
					<div class="small text-muted mt-2"><?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_DELIVERY_DETAIL', (int) $delivery['sent'], (int) $delivery['failed'], (int) $delivery['bounced']); ?></div>
				</div></div>
			</a>
		</div>
	</div>

	<div class="card mb-4">
		<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_ATTENTION'); ?></strong></div>
		<div class="card-body">
			<?php if ($issues === []) : ?>
				<div class="d-flex align-items-center gap-2"><span class="icon-check-circle text-success" aria-hidden="true"></span><strong><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_ALL_GOOD'); ?></strong><span class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_ALL_GOOD_DESC'); ?></span></div>
			<?php else : ?>
				<div class="d-grid gap-2">
				<?php foreach ($issues as $issue) : ?>
					<div class="alert alert-<?php echo htmlspecialchars((string) $issue['class'], ENT_QUOTES, 'UTF-8'); ?> d-flex justify-content-between align-items-center gap-3 mb-0">
						<span><?php echo $issue['text']; ?></span>
						<a class="btn btn-sm btn-outline-dark flex-shrink-0" href="<?php echo Route::_((string) $issue['url']); ?>"><?php echo Text::_('COM_PUNGAMAIL_REVIEW'); ?></a>
					</div>
				<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="card mb-4">
		<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_QUICK_ACTIONS'); ?></strong></div>
		<div class="card-body d-flex flex-wrap gap-2">
			<a class="btn btn-primary" href="<?php echo Route::_(AdministratorRoute::newsletter()); ?>"><?php echo Text::_('COM_PUNGAMAIL_NEW_NEWSLETTER'); ?></a>
			<a class="btn btn-outline-primary" href="<?php echo Route::_(AdministratorRoute::digest()); ?>"><?php echo Text::_('COM_PUNGAMAIL_NEW_DIGEST'); ?></a>
			<a class="btn btn-outline-secondary" href="<?php echo Route::_(AdministratorRoute::subscriber()); ?>"><?php echo Text::_('COM_PUNGAMAIL_ADD_SUBSCRIBER'); ?></a>
			<a class="btn btn-outline-secondary" href="<?php echo Route::_(AdministratorRoute::topics()); ?>"><?php echo Text::_('COM_PUNGAMAIL_TOPICS'); ?></a>
			<?php if ((int) ($queue['pending'] ?? 0) > 0 || (int) ($queue['processing'] ?? 0) > 0 || !$taskReady($tasks['queue'])) : ?>
				<form action="<?php echo Route::_('index.php?option=com_pungamail&view=dashboard'); ?>" method="post" class="d-inline">
					<button type="submit" class="btn btn-outline-secondary"><?php echo Text::_('COM_PUNGAMAIL_PROCESS_QUEUE'); ?></button>
					<input type="hidden" name="task" value="newsletter.processQueue">
					<input type="hidden" name="return" value="dashboard">
					<?php echo HTMLHelper::_('form.token'); ?>
				</form>
			<?php endif; ?>
		</div>
	</div>

	<div class="row g-3 mb-4">
		<div class="col-12 col-xl-7">
			<div class="card h-100">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_RECENT_ACTIVITY'); ?></strong></div>
				<div class="card-body">
				<?php if ($activity === []) : ?>
					<div class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_NO_ACTIVITY'); ?></div>
				<?php else : ?>
					<div class="d-grid gap-3">
					<?php foreach ($activity as $entry) : ?>
						<div class="pm-dashboard-activity">
							<div class="small text-muted"><?php echo HTMLHelper::_('date', (string) $entry['at'], Text::_('DATE_FORMAT_LC5'), 'UTC'); ?></div>
							<div>
							<?php if ((string) $entry['type'] === 'sent') : ?>
								<?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_ACTIVITY_SENT', htmlspecialchars((string) $entry['title'], ENT_QUOTES, 'UTF-8'), (int) $entry['count']); ?>
							<?php elseif ((string) $entry['type'] === 'automatic_draft') : ?>
								<?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_ACTIVITY_DRAFT', htmlspecialchars((string) $entry['title'], ENT_QUOTES, 'UTF-8'), (int) $entry['count']); ?>
							<?php elseif ((string) $entry['type'] === 'automatic_queued') : ?>
								<?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_ACTIVITY_QUEUED', htmlspecialchars((string) $entry['title'], ENT_QUOTES, 'UTF-8'), (int) $entry['count']); ?>
							<?php else : ?>
								<?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_ACTIVITY_NO_CONTENT', htmlspecialchars((string) $entry['title'], ENT_QUOTES, 'UTF-8')); ?>
							<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
					</div>
				<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="col-12 col-xl-5">
			<div class="card h-100">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_UPCOMING'); ?></strong></div>
				<div class="card-body">
				<?php if ($upcoming === []) : ?>
					<div class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_NO_UPCOMING'); ?></div>
				<?php else : ?>
					<div class="list-group list-group-flush">
					<?php foreach ($upcoming as $row) : ?>
						<a class="list-group-item list-group-item-action px-0" href="<?php echo Route::_(AdministratorRoute::newsletter((int) $row->id)); ?>">
							<div class="fw-semibold"><?php echo htmlspecialchars((string) $row->title, ENT_QUOTES, 'UTF-8'); ?></div>
							<div class="small text-muted"><?php echo HTMLHelper::_('date', $row->scheduled_at, Text::_('DATE_FORMAT_LC5'), 'UTC'); ?></div>
						</a>
					<?php endforeach; ?>
					</div>
				<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-3 mb-4">
		<div class="col-12 col-xl-7">
			<div class="card h-100">
				<div class="card-header d-flex justify-content-between align-items-center"><strong><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_DELIVERY_30_DAYS'); ?></strong><span class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_SENT_TOTAL', (int) $delivery['sent']); ?></span></div>
				<div class="card-body">
					<div class="pm-dashboard-chart" role="img" aria-label="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_DASHBOARD_DELIVERY_CHART_ALT'), ENT_QUOTES, 'UTF-8'); ?>">
					<?php foreach ($chart as $point) : ?>
						<?php $total = (int) ($point['sent'] ?? 0) + (int) ($point['failed'] ?? 0); $height = max(2, (int) round(($total / $chartMax) * 100)); ?>
						<div class="pm-dashboard-bar-wrap" title="<?php echo htmlspecialchars((string) $point['day'] . ': ' . $total, ENT_QUOTES, 'UTF-8'); ?>"><div class="pm-dashboard-bar" style="height:<?php echo $height; ?>%"></div></div>
					<?php endforeach; ?>
					</div>
					<div class="d-flex justify-content-between small text-muted"><span><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_30_DAYS_AGO'); ?></span><span><?php echo Text::_('JTODAY'); ?></span></div>
				</div>
			</div>
		</div>
		<div class="col-12 col-xl-5">
			<div class="card h-100">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_CHANNELS'); ?></strong></div>
				<div class="table-responsive">
					<table class="table table-sm align-middle mb-0"><thead><tr><th><?php echo Text::_('COM_PUNGAMAIL_CHANNEL'); ?></th><th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBERS'); ?></th></tr></thead><tbody>
					<?php if ($channelStats === []) : ?><tr><td colspan="2" class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_NO_CHANNELS'); ?></td></tr><?php endif; ?>
					<?php foreach ($channelStats as $row) : ?><tr><td><a href="<?php echo Route::_(AdministratorRoute::topic((int) $row->id)); ?>"><?php echo htmlspecialchars((string) $row->title, ENT_QUOTES, 'UTF-8'); ?></a></td><td class="text-end"><?php echo (int) $row->members; ?></td></tr><?php endforeach; ?>
					</tbody></table>
				</div>
			</div>
		</div>
	</div>

	<div class="card mb-3">
		<div class="card-header d-flex justify-content-between align-items-center"><strong><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_AUTOMATIC'); ?></strong><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_(AdministratorRoute::digests()); ?>"><?php echo Text::_('COM_PUNGAMAIL_VIEW_ALL'); ?></a></div>
		<div class="table-responsive">
			<table class="table align-middle mb-0"><thead><tr><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_NEXT_RUN'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_DIGEST_MODE'); ?></th></tr></thead><tbody>
			<?php if ($automatic === []) : ?><tr><td colspan="3" class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_DASHBOARD_NO_AUTOMATIC'); ?></td></tr><?php endif; ?>
			<?php foreach ($automatic as $row) : ?><tr><td><a href="<?php echo Route::_(AdministratorRoute::digest((int) $row->id)); ?>"><?php echo htmlspecialchars((string) $row->title, ENT_QUOTES, 'UTF-8'); ?></a></td><td><?php echo HTMLHelper::_('date', $row->next_run_at, Text::_('DATE_FORMAT_LC5'), 'UTC'); ?></td><td><?php echo Text::_((string) $row->generation_mode === 'auto' ? 'COM_PUNGAMAIL_DIGEST_AUTO' : 'COM_PUNGAMAIL_DIGEST_DRAFT'); ?></td></tr><?php endforeach; ?>
			</tbody></table>
		</div>
	</div>
</div>
