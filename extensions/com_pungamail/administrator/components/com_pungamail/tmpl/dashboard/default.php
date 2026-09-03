<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Dashboard\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$data = $this->data;
$subscribers = $data['subscribers'];
$queue = $data['queue'];
$newsletters = $data['newsletters'];
$tasks = $data['tasks'];
$automation = $data['automation'];
$taskReady = static fn (?object $task): bool => $task !== null && (int) $task->state === 1;
$taskLabels = [
	'queue' => 'COM_PUNGAMAIL_TASK_QUEUE',
	'scheduled' => 'COM_PUNGAMAIL_TASK_SCHEDULED_SENDS',
	'digests' => 'COM_PUNGAMAIL_TASK_DIGESTS',
	'bounces' => 'COM_PUNGAMAIL_TASK_BOUNCES',
];
?>
<div class="container-fluid">
	<?php if ((bool) ($data['schema_incomplete'] ?? false)) : ?>
		<div class="alert alert-danger"><?php echo Text::_('COM_PUNGAMAIL_DATABASE_UPDATE_REQUIRED'); ?></div>
	<?php endif; ?>
	<div class="row g-3 mb-3">
		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100"><div class="card-body">
				<div class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_VERSION'); ?></div>
				<div class="display-6">Punga Mail <?php echo htmlspecialchars((string) $data['version'], ENT_QUOTES, 'UTF-8'); ?></div>
			</div></div>
		</div>
		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100"><div class="card-body">
				<div class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBERS'); ?></div>
				<div class="display-6"><?php echo (int) $subscribers['subscribed']; ?></div>
				<div class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_SUBSCRIBER_DETAIL', (int) $subscribers['pending'], (int) $subscribers['suppressed']); ?></div>
			</div></div>
		</div>
		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100"><div class="card-body">
				<div class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTERS'); ?></div>
				<div class="display-6"><?php echo (int) $newsletters['active']; ?></div>
				<div class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_NEWSLETTER_DETAIL', (int) $newsletters['drafts'], (int) $newsletters['sent'], (int) $newsletters['trashed']); ?></div>
			</div></div>
		</div>
		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100"><div class="card-body">
				<div class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_MAIL_QUEUE'); ?></div>
				<div class="display-6"><?php echo (int) $queue['pending']; ?></div>
				<div class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_QUEUE_DETAIL', (int) $queue['processing'], (int) $queue['failed']); ?></div>
				<form action="<?php echo Route::_('index.php?option=com_pungamail&view=dashboard'); ?>" method="post" class="mt-3">
					<button type="submit" class="btn btn-outline-primary btn-sm"><?php echo Text::_('COM_PUNGAMAIL_PROCESS_QUEUE'); ?></button>
					<input type="hidden" name="task" value="newsletter.processQueue">
					<input type="hidden" name="return" value="dashboard">
					<?php echo HTMLHelper::_('form.token'); ?>
				</form>
			</div></div>
		</div>
	</div>

	<?php if (!$taskReady($tasks['queue'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_PUNGAMAIL_TASK_NOT_CONFIGURED'); ?></div>
	<?php endif; ?>
	<?php if ((int) $automation['digests'] > 0 && !$taskReady($tasks['digests'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_TASK_NOT_CONFIGURED'); ?></div>
	<?php endif; ?>
	<?php if ((int) $automation['scheduled'] > 0 && !$taskReady($tasks['scheduled'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_PUNGAMAIL_SCHEDULED_SEND_TASK_NOT_CONFIGURED'); ?></div>
	<?php endif; ?>
	<?php if ($automation['bounce_configured'] && !$taskReady($tasks['bounces'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_PUNGAMAIL_BOUNCE_TASK_NOT_CONFIGURED'); ?></div>
	<?php endif; ?>

	<div class="row g-3">
		<div class="col-12 col-xl-7">
			<div class="card h-100">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_QUICK_ACTIONS'); ?></strong></div>
				<div class="card-body d-flex flex-wrap gap-2 align-items-start">
					<a class="btn btn-primary" href="<?php echo Route::_(\Punga\Component\PungaMail\Administrator\Service\AdministratorRoute::newsletter()); ?>"><?php echo Text::_('COM_PUNGAMAIL_NEW_NEWSLETTER'); ?></a>
					<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletters'); ?>"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTERS'); ?></a>
					<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=templates'); ?>"><?php echo Text::_('COM_PUNGAMAIL_TEMPLATES'); ?></a>
					<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=subscribers'); ?>"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBERS'); ?></a>
				</div>
			</div>
		</div>
		<div class="col-12 col-xl-5">
			<div class="card h-100">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_SCHEDULED_TASKS'); ?></strong></div>
				<div class="table-responsive">
					<table class="table table-sm align-middle mb-0">
						<thead><tr><th><?php echo Text::_('COM_PUNGAMAIL_TASK_TYPE'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_NEXT_RUN'); ?></th></tr></thead>
						<tbody>
						<?php foreach ($taskLabels as $key => $label) : ?>
							<?php $schedulerTask = $tasks[$key]; ?>
							<tr>
								<td><?php echo Text::_($label); ?></td>
								<td><?php echo $schedulerTask === null ? Text::_('COM_PUNGAMAIL_NOT_CONFIGURED') : ((int) $schedulerTask->state === 1 ? Text::_('JENABLED') : Text::_('JDISABLED')); ?></td>
								<td><?php echo $schedulerTask !== null && $schedulerTask->next_execution ? HTMLHelper::_('date', $schedulerTask->next_execution, Text::_('DATE_FORMAT_LC5'), 'UTC') : '—'; ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="card-footer"><a class="btn btn-outline-primary btn-sm" href="<?php echo Route::_('index.php?option=com_scheduler&view=tasks'); ?>"><?php echo Text::_('COM_PUNGAMAIL_OPEN_SCHEDULED_TASKS'); ?></a></div>
				</div>
			</div>
		</div>
	</div>
</div>
