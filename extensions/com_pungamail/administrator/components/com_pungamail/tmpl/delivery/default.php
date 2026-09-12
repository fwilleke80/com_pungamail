<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Delivery\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;

$settings = $this->settings;
$bounceCheck = $this->bounceCheck;
$diagnostics = $this->diagnostics;
$identity = Factory::getApplication()->getIdentity();
$siteTimezone = (string) Factory::getApplication()->get('offset', 'UTC');
$softBounceThreshold = max(1, (int) ComponentHelper::getParams('com_pungamail')->get('soft_bounce_threshold', 3));
$queueStatusKeys = [
	'pending' => 'COM_PUNGAMAIL_QUEUE_STATUS_PENDING',
	'processing' => 'COM_PUNGAMAIL_QUEUE_STATUS_PROCESSING',
	'sent' => 'COM_PUNGAMAIL_QUEUE_STATUS_SENT',
	'failed' => 'COM_PUNGAMAIL_QUEUE_STATUS_FAILED',
	'cancelled' => 'COM_PUNGAMAIL_QUEUE_STATUS_CANCELLED',
	'bounced' => 'COM_PUNGAMAIL_QUEUE_STATUS_BOUNCED',
];
?>
<div class="card mb-3">
	<div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
		<strong><?php echo Text::_('COM_PUNGAMAIL_MAIL_QUEUE'); ?></strong>
		<span class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_QUEUE_SHOWING', count($this->queue)); ?></span>
	</div>
	<div class="card-body border-bottom">
		<form class="row g-2 align-items-end" action="<?php echo Route::_('index.php'); ?>" method="get">
			<input type="hidden" name="option" value="com_pungamail">
			<input type="hidden" name="view" value="delivery">
			<div class="col-12 col-md-3">
				<label class="form-label" for="queue-status"><?php echo Text::_('JSTATUS'); ?></label>
				<select class="form-select" id="queue-status" name="queue_status">
					<option value=""><?php echo Text::_('COM_PUNGAMAIL_QUEUE_STATUS_ALL'); ?></option>
					<?php foreach ($queueStatusKeys as $status => $labelKey) : ?>
						<option value="<?php echo $status; ?>" <?php echo ($this->queueFilters['status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo Text::_($labelKey); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-md-3">
				<label class="form-label" for="queue-newsletter"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER'); ?></label>
				<select class="form-select" id="queue-newsletter" name="queue_newsletter">
					<option value="0"><?php echo Text::_('COM_PUNGAMAIL_QUEUE_NEWSLETTER_ALL'); ?></option>
					<?php foreach ($this->queueNewsletters as $newsletter) : ?>
						<option value="<?php echo (int) $newsletter->id; ?>" <?php echo (int) ($this->queueFilters['newsletter_id'] ?? 0) === (int) $newsletter->id ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $newsletter->title, ENT_QUOTES, 'UTF-8'); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-md-2">
				<label class="form-label" for="queue-archive"><?php echo Text::_('COM_PUNGAMAIL_QUEUE_VISIBILITY'); ?></label>
				<select class="form-select" id="queue-archive" name="queue_archive">
					<option value="active" <?php echo ($this->queueFilters['archive'] ?? 'active') === 'active' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_QUEUE_ACTIVE'); ?></option>
					<option value="archived" <?php echo ($this->queueFilters['archive'] ?? 'active') === 'archived' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_QUEUE_ARCHIVED'); ?></option>
					<option value="all" <?php echo ($this->queueFilters['archive'] ?? 'active') === 'all' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_QUEUE_ACTIVE_AND_ARCHIVED'); ?></option>
				</select>
			</div>
			<div class="col-12 col-md-2">
				<label class="form-label" for="queue-search"><?php echo Text::_('JSEARCH_FILTER'); ?></label>
				<input class="form-control" id="queue-search" name="queue_search" type="search" value="<?php echo htmlspecialchars((string) ($this->queueFilters['search'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_QUEUE_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>">
			</div>
			<div class="col-12 col-md-2 d-flex gap-2">
				<button class="btn btn-primary" type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
				<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=delivery'); ?>"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
			</div>
		</form>
	</div>
	<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post">
		<div class="table-responsive">
			<table class="table table-striped align-middle mb-0">
				<thead><tr>
					<th class="text-center" style="width:1%"><input class="form-check-input" type="checkbox" id="queue-check-all" aria-label="<?php echo htmlspecialchars(Text::_('JGLOBAL_CHECK_ALL'), ENT_QUOTES, 'UTF-8'); ?>"></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER'); ?></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_RECIPIENT'); ?></th>
					<th><?php echo Text::_('JSTATUS'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_PUNGAMAIL_ATTEMPTS'); ?></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_QUEUE_NEXT_ATTEMPT'); ?></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_QUEUE_CREATED'); ?></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_QUEUE_MODIFIED'); ?></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_SENT'); ?></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_QUEUE_LAST_ERROR'); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ($this->queue as $entry) : ?>
					<?php $statusKey = $queueStatusKeys[(string) $entry->status] ?? 'COM_PUNGAMAIL_STATUS_UNKNOWN'; ?>
					<tr>
						<td class="text-center"><input class="form-check-input pm-queue-check" type="checkbox" name="queue_ids[]" value="<?php echo (int) $entry->id; ?>" aria-label="<?php echo (int) $entry->id; ?>"></td>
						<td><a href="<?php echo Route::_(AdministratorRoute::newsletter((int) $entry->newsletter_id)); ?>"><?php echo htmlspecialchars((string) ($entry->newsletter_title ?: ('#' . $entry->newsletter_id)), ENT_QUOTES, 'UTF-8'); ?></a></td>
						<td><div><?php echo htmlspecialchars((string) (($entry->recipient_name ?? '') ?: $entry->email), ENT_QUOTES, 'UTF-8'); ?></div><code class="small"><?php echo htmlspecialchars((string) $entry->email, ENT_QUOTES, 'UTF-8'); ?></code></td>
						<td><?php echo Text::_($statusKey); ?><?php if ((int) ($entry->archived ?? 0) === 1) : ?><div><span class="badge bg-secondary mt-1"><?php echo Text::_('COM_PUNGAMAIL_ARCHIVED_NOTE'); ?></span></div><?php endif; ?></td>
						<td class="text-end"><?php echo (int) $entry->attempts; ?></td>
						<td><?php echo $entry->next_attempt_at ? HTMLHelper::_('date', $entry->next_attempt_at, Text::_('DATE_FORMAT_LC5'), $siteTimezone) : '—'; ?></td>
						<td><?php echo HTMLHelper::_('date', $entry->created, Text::_('DATE_FORMAT_LC5'), $siteTimezone); ?></td>
						<td><?php echo HTMLHelper::_('date', $entry->modified, Text::_('DATE_FORMAT_LC5'), $siteTimezone); ?></td>
						<td><?php echo $entry->sent_at ? HTMLHelper::_('date', $entry->sent_at, Text::_('DATE_FORMAT_LC5'), $siteTimezone) : '—'; ?></td>
						<td class="small text-danger" style="max-width:24rem;white-space:normal"><?php echo htmlspecialchars((string) ($entry->last_error ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ($this->queue === []) : ?><tr><td colspan="10" class="text-center text-muted py-4"><?php echo Text::_('COM_PUNGAMAIL_QUEUE_NO_ENTRIES'); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="card-footer d-flex flex-wrap gap-2">
			<button class="btn btn-outline-primary" type="submit" formaction="<?php echo Route::_('index.php?option=com_pungamail&task=delivery.retryQueue'); ?>"><?php echo Text::_('COM_PUNGAMAIL_RETRY_SELECTED'); ?></button>
			<button class="btn btn-outline-danger" type="submit" formaction="<?php echo Route::_('index.php?option=com_pungamail&task=delivery.cancelQueue'); ?>" onclick="return confirm('<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_QUEUE_CANCEL_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>');"><?php echo Text::_('COM_PUNGAMAIL_CANCEL_SELECTED'); ?></button>
			<?php if (($this->queueFilters['archive'] ?? 'active') !== 'archived') : ?>
				<button class="btn btn-outline-secondary" type="submit" formaction="<?php echo Route::_('index.php?option=com_pungamail&task=delivery.archiveQueue'); ?>" onclick="return confirm('<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_QUEUE_ARCHIVE_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>');"><?php echo Text::_('COM_PUNGAMAIL_ARCHIVE_SELECTED'); ?></button>
			<?php endif; ?>
			<?php if (($this->queueFilters['archive'] ?? 'active') !== 'active') : ?>
				<button class="btn btn-outline-secondary" type="submit" formaction="<?php echo Route::_('index.php?option=com_pungamail&task=delivery.unarchiveQueue'); ?>"><?php echo Text::_('COM_PUNGAMAIL_UNARCHIVE_SELECTED'); ?></button>
			<?php endif; ?>
		</div>
		<input type="hidden" name="queue_status" value="<?php echo htmlspecialchars((string) ($this->queueFilters['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
		<input type="hidden" name="queue_newsletter" value="<?php echo (int) ($this->queueFilters['newsletter_id'] ?? 0); ?>">
		<input type="hidden" name="queue_search" value="<?php echo htmlspecialchars((string) ($this->queueFilters['search'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
		<input type="hidden" name="queue_archive" value="<?php echo htmlspecialchars((string) ($this->queueFilters['archive'] ?? 'active'), ENT_QUOTES, 'UTF-8'); ?>">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function ()
{
	const all = document.getElementById('queue-check-all');
	const checks = Array.from(document.querySelectorAll('.pm-queue-check'));

	if (all)
	{
		all.addEventListener('change', function ()
		{
			checks.forEach(function (check)
			{
				check.checked = all.checked;
			});
		});
	}
});
</script>

<div class="row g-3">
	<div class="col-12 col-xl-7">
		<div class="card mb-3">
			<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_BOUNCE_MAILBOX'); ?></strong></div>
			<div class="card-body">
				<p><?php echo Text::_('COM_PUNGAMAIL_BOUNCE_SETTINGS_IN_OPTIONS'); ?></p>
				<dl class="row mb-3">
					<dt class="col-sm-4"><?php echo Text::_('COM_PUNGAMAIL_CONFIGURATION_STATUS'); ?></dt>
					<dd class="col-sm-8"><?php echo trim((string) $settings->bounce_host) !== '' && $settings->password_configured ? Text::_('COM_PUNGAMAIL_CONFIGURED') : Text::_('COM_PUNGAMAIL_NOT_CONFIGURED'); ?></dd>
					<dt class="col-sm-4"><?php echo Text::_('COM_PUNGAMAIL_SERVER'); ?></dt>
					<dd class="col-sm-8"><code><?php echo htmlspecialchars((string) ($settings->bounce_host ?: '—'), ENT_QUOTES, 'UTF-8'); ?></code></dd>
				</dl>
				<div class="d-flex flex-wrap gap-2">
					<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_pungamail'); ?>"><?php echo Text::_('COM_PUNGAMAIL_OPEN_COMPONENT_OPTIONS'); ?></a>
					<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post">
						<input type="hidden" name="task" value="delivery.processBounces">
						<button class="btn btn-outline-secondary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_PROCESS_BOUNCES_NOW'); ?></button>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div>

				<div class="border rounded p-3 mt-3">
					<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
						<strong><?php echo Text::_('COM_PUNGAMAIL_RETURNED_MAIL_LAST_CHECK'); ?></strong>
						<?php if ($bounceCheck !== null) : ?>
							<span class="badge <?php echo $bounceCheck->ok ? 'bg-success' : 'bg-danger'; ?>"><?php echo Text::_($bounceCheck->ok ? 'COM_PUNGAMAIL_CHECK_SUCCEEDED' : 'COM_PUNGAMAIL_CHECK_FAILED'); ?></span>
						<?php endif; ?>
					</div>
					<?php if ($bounceCheck === null) : ?>
						<div class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_RETURNED_MAIL_NOT_CHECKED'); ?></div>
					<?php else : ?>
						<div class="small text-muted mb-2"><?php echo Text::_('COM_PUNGAMAIL_LAST_CHECK'); ?>: <?php echo HTMLHelper::_('date', $bounceCheck->checked_at, Text::_('DATE_FORMAT_LC5'), $siteTimezone); ?></div>
						<?php if ($bounceCheck->ok) : ?>
							<div><?php echo Text::sprintf('COM_PUNGAMAIL_RETURNED_MAIL_LAST_RESULT', (int) $bounceCheck->processed, (int) $bounceCheck->hard, (int) $bounceCheck->soft); ?></div>
							<?php if ((int) $bounceCheck->suppressed > 0) : ?>
								<div class="alert alert-warning py-2 px-3 mt-2 mb-0"><?php echo Text::plural('COM_PUNGAMAIL_RETURNED_MAIL_NEW_SUPPRESSIONS', (int) $bounceCheck->suppressed); ?></div>
							<?php endif; ?>
						<?php else : ?>
							<div class="text-danger"><?php echo Text::_('COM_PUNGAMAIL_RETURNED_MAIL_CHECK_FAILED'); ?><?php if ($bounceCheck->error !== '') : ?>: <?php echo htmlspecialchars((string) $bounceCheck->error, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="card" id="returned-mail">
			<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_RECENT_BOUNCES'); ?></strong></div>
			<div class="table-responsive"><table class="table mb-0"><thead><tr><th><?php echo Text::_('JDATE'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_EMAIL'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_CLASSIFICATION'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SMTP_STATUS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_DETAILS'); ?></th></tr></thead><tbody>
			<?php foreach ($this->bounces as $bounce) : ?>
				<?php
				$classification = (string) ($bounce->classification ?? 'unknown');
				$classificationKey = match ($classification)
				{
					'hard' => 'COM_PUNGAMAIL_BOUNCE_CLASS_PERMANENT',
					'soft' => 'COM_PUNGAMAIL_BOUNCE_CLASS_TEMPORARY',
					default => 'COM_PUNGAMAIL_BOUNCE_CLASS_UNKNOWN',
				};
				?>
				<tr>
					<td><?php echo HTMLHelper::_('date', $bounce->occurred_at, Text::_('DATE_FORMAT_LC5'), 'UTC'); ?></td>
					<td><code><?php echo htmlspecialchars((string) $bounce->email, ENT_QUOTES, 'UTF-8'); ?></code><?php if ($bounce->suppression_reason) : ?><div class="badge bg-danger mt-1"><?php echo Text::_('COM_PUNGAMAIL_DELIVERY_BLOCKED'); ?></div><?php endif; ?></td>
					<td>
						<strong><?php echo Text::_($classificationKey); ?></strong>
						<?php if ($classification === 'hard') : ?>
							<div class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_PERMANENT_FAILURE_IMMEDIATE'); ?></div>
						<?php elseif ($classification === 'soft' && (string) ($bounce->suppression_reason ?? '') === 'soft-bounce-threshold') : ?>
							<div class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_TEMPORARY_FAILURE_THRESHOLD_REACHED', $softBounceThreshold); ?></div>
						<?php elseif ($classification === 'soft' && (int) ($bounce->current_soft_bounce_count ?? 0) > 0) : ?>
							<div class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_TEMPORARY_FAILURE_PROGRESS', (int) $bounce->current_soft_bounce_count, $softBounceThreshold); ?></div>
						<?php endif; ?>
					</td>
					<td><?php echo htmlspecialchars((string) ($bounce->status_code ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
					<td class="small"><?php echo htmlspecialchars((string) ($bounce->diagnostic ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ($this->bounces === []) : ?><tr><td colspan="5" class="text-center text-muted py-4"><?php echo Text::_('COM_PUNGAMAIL_NO_BOUNCES'); ?></td></tr><?php endif; ?>
			</tbody></table></div>
		</div>
	</div>
	<div class="col-12 col-xl-5">
		<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_QUEUE_CONTROL'); ?></strong></div><div class="card-body"><p><?php echo Text::_($diagnostics['queue_paused'] ? 'COM_PUNGAMAIL_QUEUE_IS_PAUSED' : 'COM_PUNGAMAIL_QUEUE_IS_RUNNING'); ?></p><form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post"><input type="hidden" name="task" value="delivery.toggleQueue"><input type="hidden" name="paused" value="<?php echo $diagnostics['queue_paused'] ? 0 : 1; ?>"><button class="btn <?php echo $diagnostics['queue_paused'] ? 'btn-success' : 'btn-warning'; ?>" type="submit"><?php echo Text::_($diagnostics['queue_paused'] ? 'COM_PUNGAMAIL_RESUME_QUEUE' : 'COM_PUNGAMAIL_PAUSE_QUEUE'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form></div></div>
		<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_MAIL_TEST'); ?></strong></div><div class="card-body"><form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post"><label class="form-label" for="test-email"><?php echo Text::_('COM_PUNGAMAIL_RECIPIENT_EMAIL'); ?></label><div class="input-group"><input required class="form-control" id="test-email" name="test_email" type="email" value="<?php echo htmlspecialchars((string) $identity->email, ENT_QUOTES, 'UTF-8'); ?>"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_SEND_TEST_MAIL'); ?></button></div><input type="hidden" name="task" value="delivery.sendTest"><?php echo HTMLHelper::_('form.token'); ?></form></div></div>
		<div class="card"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DIAGNOSTICS'); ?></strong></div><div class="card-body"><dl class="row mb-0"><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_OUTGOING_TRANSPORT'); ?></dt><dd class="col-6"><?php echo Text::_($diagnostics['transport_source'] === 'custom' ? 'COM_PUNGAMAIL_CUSTOM_SMTP' : 'COM_PUNGAMAIL_USE_JOOMLA_MAIL_SETTINGS'); ?> <span class="text-muted">(<code><?php echo htmlspecialchars((string) $diagnostics['mailer'], ENT_QUOTES, 'UTF-8'); ?></code>)</span><?php if ($diagnostics['transport_source'] === 'custom' && $diagnostics['smtp_host'] !== '') : ?><div class="small text-muted"><code><?php echo htmlspecialchars((string) $diagnostics['smtp_host'], ENT_QUOTES, 'UTF-8'); ?></code></div><?php endif; ?></dd><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_SENDER'); ?></dt><dd class="col-6"><?php echo htmlspecialchars((string) $diagnostics['sender_email'], ENT_QUOTES, 'UTF-8'); ?> <?php echo $diagnostics['sender_valid'] ? '✓' : '⚠'; ?></dd><dt class="col-6">PHP IMAP</dt><dd class="col-6"><?php echo Text::_($diagnostics['imap_available'] ? 'JYES' : 'JNO'); ?></dd><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_CONFIG_BATCH_SIZE'); ?></dt><dd class="col-6"><?php echo (int) $diagnostics['batch_size']; ?></dd><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_CONFIG_MAX_ATTEMPTS'); ?></dt><dd class="col-6"><?php echo (int) $diagnostics['max_attempts']; ?></dd><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_CONFIG_RETRY_MINUTES'); ?></dt><dd class="col-6"><?php echo (int) $diagnostics['retry_minutes']; ?></dd></dl></div></div>
	</div>
</div>
