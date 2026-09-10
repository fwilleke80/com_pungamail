<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Template
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

/** @var \Punga\Component\PungaMail\Administrator\View\Digest\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRepository;

$item = $this->item;
$siteTimezone = (string) Factory::getApplication()->get('offset', 'UTC');
$next = $item
	? HTMLHelper::_('date', $item->next_run_at, 'Y-m-d H:i:s', $siteTimezone)
	: HTMLHelper::_('date', 'now', 'Y-m-d H:i:s', $siteTimezone);
$confirmAutoChecked = is_object($item) && property_exists($item, 'confirm_auto_send')
	? (int) $item->confirm_auto_send === 1
	: (string) ($item->generation_mode ?? 'draft') === 'auto';
$storedRecurrenceUnit = (string) ($item->recurrence_unit ?? 'weeks');
$recurrenceUnit = in_array($storedRecurrenceUnit, ['days', 'weeks', 'months'], true) ? $storedRecurrenceUnit : 'days';
$recurrenceValue = $storedRecurrenceUnit === 'legacy'
	? max(1, (int) round(((int) ($item->recurrence_minutes ?? 1440)) / 1440))
	: max(1, (int) ($item->recurrence_value ?? 1));
$contentOrder = (string) ($item->content_order ?? 'newest') === 'oldest' ? 'oldest' : 'newest';
$maxItems = max(0, (int) ($item->max_items ?? 0));
$minimumItems = max(0, (int) ($item->minimum_items ?? 0));

$runStatus = static function (string $status): array
{
	return match ($status)
	{
		'running' => [Text::_('COM_PUNGAMAIL_DIGEST_RUN_RUNNING'), 'text-bg-info'],
		'draft' => [Text::_('COM_PUNGAMAIL_DIGEST_RUN_DRAFT_CREATED'), 'text-bg-primary'],
		'queued' => [Text::_('COM_PUNGAMAIL_DIGEST_RUN_QUEUED'), 'text-bg-success'],
		'no_content' => [Text::_('COM_PUNGAMAIL_DIGEST_RUN_NO_CONTENT'), 'text-bg-secondary'],
		'below_minimum' => [Text::_('COM_PUNGAMAIL_DIGEST_RUN_BELOW_MINIMUM'), 'text-bg-warning'],
		'failed' => [Text::_('COM_PUNGAMAIL_DIGEST_RUN_FAILED'), 'text-bg-danger'],
		default => [$status, 'text-bg-secondary'],
	};
};

$newsletterStatus = static function (?int $status): string
{
	return match ($status)
	{
		NewsletterRepository::STATUS_DRAFT => Text::_('COM_PUNGAMAIL_STATUS_DRAFT'),
		NewsletterRepository::STATUS_QUEUED => Text::_('COM_PUNGAMAIL_STATUS_QUEUED'),
		NewsletterRepository::STATUS_SENDING => Text::_('COM_PUNGAMAIL_STATUS_SENDING'),
		NewsletterRepository::STATUS_SENT => Text::_('COM_PUNGAMAIL_STATUS_SENT'),
		NewsletterRepository::STATUS_SENT_WITH_FAILURES => Text::_('COM_PUNGAMAIL_STATUS_SENT_WITH_FAILURES'),
		NewsletterRepository::STATUS_SCHEDULED => Text::_('COM_PUNGAMAIL_STATUS_SCHEDULED'),
		NewsletterRepository::STATUS_FAILED => Text::_('COM_PUNGAMAIL_STATUS_FAILED'),
		NewsletterRepository::STATUS_CANCELLED => Text::_('COM_PUNGAMAIL_STATUS_CANCELLED'),
		default => '',
	};
};
?>
<form action="<?php echo Route::_(AdministratorRoute::digests()); ?>" method="post" name="adminForm" id="adminForm" data-pm-unsaved-warning="1">
	<div class="row g-3">
		<div class="col-lg-7">
			<div class="card mb-3">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DIGEST_BASICS'); ?></strong></div>
				<div class="card-body">
					<div class="mb-3">
						<label class="form-label" for="digest-title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label>
						<input required class="form-control" id="digest-title" name="title" value="<?php echo htmlspecialchars((string) ($item->title ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<div class="mb-3">
						<label class="form-label" for="digest-template"><?php echo Text::_('COM_PUNGAMAIL_TEMPLATE'); ?></label>
						<select required class="form-select" id="digest-template" name="template_id">
							<option value="0"><?php echo Text::_('JSELECT'); ?></option>
							<?php foreach ($this->templates as $template) : ?>
								<option value="<?php echo (int) $template->id; ?>" <?php echo (int) ($item->template_id ?? 0) === (int) $template->id ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $template->title, ENT_QUOTES, 'UTF-8'); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label" for="digest-subject"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_SUBJECT_PATTERN'); ?></label>
						<input class="form-control" id="digest-subject" name="subject_pattern" value="<?php echo htmlspecialchars((string) ($item->subject_pattern ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
						<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_SUBJECT_HELP'); ?></div>
					</div>
				</div>
			</div>

			<div class="card mb-3">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DIGEST_CONTENT'); ?></strong></div>
				<div class="card-body">
					<div class="form-text mb-3"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_ACCESS_HELP'); ?></div>
					<?php foreach ($this->contentTypes as $key => $type) : ?>
						<?php $selected = in_array((string) $key, $this->sourceKeys, true); $cats = implode(',', $this->categories[(string) $key] ?? []); ?>
						<div class="border rounded p-2 mb-2">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="source_keys[]" value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>" id="digest-source-<?php echo md5((string) $key); ?>" <?php echo $selected ? 'checked' : ''; ?>>
								<label class="form-check-label fw-semibold" for="digest-source-<?php echo md5((string) $key); ?>"><?php echo htmlspecialchars((string) $type->label, ENT_QUOTES, 'UTF-8'); ?></label>
							</div>
							<label class="form-label small mt-2" for="digest-categories-<?php echo md5((string) $key); ?>"><?php echo Text::_('COM_PUNGAMAIL_CATEGORY_IDS'); ?></label>
							<input class="form-control form-control-sm" id="digest-categories-<?php echo md5((string) $key); ?>" name="source_categories[<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars($cats, ENT_QUOTES, 'UTF-8'); ?>" placeholder="1, 4, 12">
						</div>
					<?php endforeach; ?>

					<hr>
					<div class="row g-3">
						<div class="col-md-4">
							<label class="form-label" for="digest-content-order"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_ORDER'); ?></label>
							<select class="form-select" id="digest-content-order" name="content_order">
								<option value="newest" <?php echo $contentOrder === 'newest' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CONTENT_ORDER_NEWEST'); ?></option>
								<option value="oldest" <?php echo $contentOrder === 'oldest' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CONTENT_ORDER_OLDEST'); ?></option>
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label" for="digest-max-items"><?php echo Text::_('COM_PUNGAMAIL_MAX_CONTENT_ITEMS'); ?></label>
							<input class="form-control" type="number" min="0" max="1000" id="digest-max-items" name="max_items" value="<?php echo $maxItems; ?>">
							<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MAX_CONTENT_ITEMS_HELP'); ?></div>
						</div>
						<div class="col-md-4">
							<label class="form-label" for="digest-min-items"><?php echo Text::_('COM_PUNGAMAIL_MIN_CONTENT_ITEMS'); ?></label>
							<input class="form-control" type="number" min="0" max="1000" id="digest-min-items" name="minimum_items" value="<?php echo $minimumItems; ?>">
							<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MIN_CONTENT_ITEMS_HELP'); ?></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="col-lg-5">
			<div class="card mb-3">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DIGEST_SCHEDULE'); ?></strong></div>
				<div class="card-body">
					<div class="mb-3">
						<label class="form-label" for="digest-next"><?php echo Text::_('COM_PUNGAMAIL_NEXT_RUN'); ?></label>
						<?php echo HTMLHelper::_('calendar', $next, 'next_run_at', 'digest-next', '%Y-%m-%d %H:%M', ['class' => 'form-control', 'required' => true, 'showTime' => true, 'timeFormat' => 24, 'todayBtn' => true, 'singleHeader' => true]); ?>
						<div class="form-text"><?php echo Text::sprintf('COM_PUNGAMAIL_SITE_TIMEZONE_HELP', $siteTimezone); ?></div>
					</div>
					<div class="mb-3">
						<label class="form-label" for="digest-repeat"><?php echo Text::_('COM_PUNGAMAIL_RECURRENCE'); ?></label>
						<div class="input-group">
							<input class="form-control" type="number" min="1" max="365" id="digest-repeat" name="recurrence_value" value="<?php echo $recurrenceValue; ?>">
							<select class="form-select" name="recurrence_unit" aria-label="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_RECURRENCE_UNIT'), ENT_QUOTES, 'UTF-8'); ?>">
								<option value="days" <?php echo $recurrenceUnit === 'days' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_DAYS'); ?></option>
								<option value="weeks" <?php echo $recurrenceUnit === 'weeks' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_WEEKS'); ?></option>
								<option value="months" <?php echo $recurrenceUnit === 'months' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_MONTHS'); ?></option>
							</select>
						</div>
						<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_RECURRENCE_HELP'); ?></div>
					</div>
					<div class="mb-3">
						<label class="form-label" for="digest-cutoff"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_CUTOFF'); ?></label>
						<select class="form-select" id="digest-cutoff" name="cutoff_mode">
							<option value="since_last" <?php echo (string) ($item->cutoff_mode ?? 'since_last') === 'since_last' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CUTOFF_SINCE_LAST'); ?></option>
							<option value="rolling" <?php echo (string) ($item->cutoff_mode ?? '') === 'rolling' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CUTOFF_ROLLING'); ?></option>
						</select>
					</div>
					<div class="mb-3" id="pm-digest-rolling-period" <?php echo (string) ($item->cutoff_mode ?? 'since_last') === 'rolling' ? '' : 'hidden'; ?>>
						<label class="form-label" for="digest-days"><?php echo Text::_('COM_PUNGAMAIL_ROLLING_DAYS'); ?></label>
						<div class="input-group">
							<input class="form-control" type="number" min="1" max="365" id="digest-days" name="rolling_days" value="<?php echo max(1, (int) round(((int) ($item->rolling_hours ?? 168)) / 24)); ?>">
							<span class="input-group-text"><?php echo Text::_('COM_PUNGAMAIL_DAYS'); ?></span>
						</div>
						<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_ROLLING_DAYS_HELP'); ?></div>
					</div>
					<div class="mb-3">
						<label class="form-label" for="digest-mode"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_MODE'); ?></label>
						<select class="form-select" id="digest-mode" name="generation_mode">
							<option value="draft" <?php echo (string) ($item->generation_mode ?? 'draft') === 'draft' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_DIGEST_CREATE_DRAFT'); ?></option>
							<option value="auto" <?php echo (string) ($item->generation_mode ?? '') === 'auto' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_DIGEST_CREATE_SEND'); ?></option>
						</select>
					</div>
					<div id="pm-digest-auto-confirm" <?php echo (string) ($item->generation_mode ?? 'draft') === 'auto' ? '' : 'hidden'; ?>>
						<div class="form-text text-danger mb-2"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_AUTO_WARNING'); ?></div>
						<div class="form-check mb-3">
							<input class="form-check-input" type="checkbox" name="confirm_auto_send" value="1" id="confirm-auto" <?php echo $confirmAutoChecked ? 'checked' : ''; ?>>
							<label class="form-check-label" for="confirm-auto"><?php echo Text::_('COM_PUNGAMAIL_CONFIRM_AUTO_SEND'); ?></label>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label" for="empty-action"><?php echo Text::_('COM_PUNGAMAIL_EMPTY_DIGEST'); ?></label>
						<select class="form-select" id="empty-action" name="empty_action">
							<option value="skip" <?php echo (string) ($item->empty_action ?? 'skip') === 'skip' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_EMPTY_SKIP'); ?></option>
							<option value="create_draft" <?php echo (string) ($item->empty_action ?? '') === 'create_draft' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_EMPTY_DRAFT'); ?></option>
						</select>
					</div>
				</div>
			</div>

			<div class="card mb-3">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_AUDIENCE'); ?></strong></div>
				<div class="card-body">
					<p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_AUDIENCE_UNION_HELP'); ?></p>
					<div id="pm-digest-audience-summary" class="alert alert-info small" role="status"
						data-none="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_NONE'), ENT_QUOTES, 'UTF-8'); ?>"
						data-all="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_ALL'), ENT_QUOTES, 'UTF-8'); ?>"
						data-topics="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_TOPICS'), ENT_QUOTES, 'UTF-8'); ?>"
						data-groups="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_GROUPS'), ENT_QUOTES, 'UTF-8'); ?>"></div>
					<div id="pm-digest-audience-warning" class="alert alert-warning small" hidden><?php echo Text::_('COM_PUNGAMAIL_AUDIENCE_ALL_TOPICS_WARNING'); ?></div>
					<div class="form-check mb-3">
						<input type="hidden" name="include_subscribers" value="0">
						<input class="form-check-input pm-digest-audience-all" type="checkbox" name="include_subscribers" value="1" id="digest-subscribers" <?php echo $item === null || (int) $item->include_subscribers === 1 ? 'checked' : ''; ?>>
						<label class="form-check-label fw-semibold" for="digest-subscribers"><?php echo Text::_('COM_PUNGAMAIL_ALL_CONFIRMED_SUBSCRIBERS'); ?></label>
						<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_ALL_SUBSCRIBERS_HELP'); ?></div>
					</div>
					<fieldset>
						<legend class="h6 mb-1"><?php echo Text::_('COM_PUNGAMAIL_TOPICS'); ?></legend>
						<div class="small text-muted mb-2"><?php echo Text::_('COM_PUNGAMAIL_TOPICS_TARGET_HELP'); ?></div>
						<?php foreach ($this->topics as $topic) : ?>
							<div class="form-check">
								<input class="form-check-input pm-digest-audience-topic" type="checkbox" name="topic_ids[]" value="<?php echo (int) $topic->id; ?>" id="digest-topic-<?php echo (int) $topic->id; ?>" <?php echo in_array((int) $topic->id, $this->topicIds, true) ? 'checked' : ''; ?>>
								<label class="form-check-label" for="digest-topic-<?php echo (int) $topic->id; ?>">
									<?php echo htmlspecialchars((string) $topic->title, ENT_QUOTES, 'UTF-8'); ?>
									<?php if ((int) $topic->state === -2) : ?><span class="badge bg-secondary ms-1"><?php echo Text::_('JTRASHED'); ?></span><?php elseif ((int) $topic->state !== 1) : ?><span class="badge bg-warning text-dark ms-1"><?php echo Text::_('JUNPUBLISHED'); ?></span><?php endif; ?>
								</label>
							</div>
						<?php endforeach; ?>
					</fieldset>
					<fieldset class="mt-3">
						<legend class="h6 mb-1"><?php echo Text::_('COM_PUNGAMAIL_ADDITIONAL_USER_GROUPS'); ?></legend>
						<div class="small text-muted mb-2"><?php echo Text::_('COM_PUNGAMAIL_GROUPS_RESPECT_OPT_OUT'); ?></div>
						<?php foreach ($this->groups as $group) : ?>
							<div class="form-check"><input class="form-check-input pm-digest-audience-group" type="checkbox" name="group_ids[]" value="<?php echo (int) $group->id; ?>" id="digest-group-<?php echo (int) $group->id; ?>" <?php echo in_array((int) $group->id, $this->groupIds, true) ? 'checked' : ''; ?>><label class="form-check-label" for="digest-group-<?php echo (int) $group->id; ?>"><?php echo htmlspecialchars((string) $group->title, ENT_QUOTES, 'UTF-8'); ?></label></div>
						<?php endforeach; ?>
					</fieldset>
				</div>
			</div>
		</div>
	</div>

	<?php if ((int) ($item->id ?? 0) > 0) : ?>
		<div class="card mt-3">
			<div class="card-header d-flex justify-content-between align-items-center">
				<strong><?php echo Text::_('COM_PUNGAMAIL_DIGEST_HISTORY'); ?></strong>
				<span class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_HISTORY_HELP'); ?></span>
			</div>
			<?php if ($this->runs === []) : ?>
				<div class="card-body text-muted"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_HISTORY_EMPTY'); ?></div>
			<?php else : ?>
				<div class="table-responsive">
					<table class="table align-middle mb-0">
						<thead>
							<tr>
								<th><?php echo Text::_('JDATE'); ?></th>
								<th><?php echo Text::_('JSTATUS'); ?></th>
								<th><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER'); ?></th>
								<th><?php echo Text::_('COM_PUNGAMAIL_CONTENT_ITEMS'); ?></th>
								<th><?php echo Text::_('COM_PUNGAMAIL_DURATION'); ?></th>
								<th><?php echo Text::_('COM_PUNGAMAIL_DETAILS'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($this->runs as $run) : ?>
								<?php
								[$statusLabel, $statusClass] = $runStatus((string) $run->status);
								$duration = '—';
								if (!empty($run->completed_at))
								{
									$seconds = max(0, strtotime((string) $run->completed_at . ' UTC') - strtotime((string) $run->started_at . ' UTC'));
									$duration = $seconds < 60 ? Text::sprintf('COM_PUNGAMAIL_DURATION_SECONDS', $seconds) : Text::sprintf('COM_PUNGAMAIL_DURATION_MINUTES', max(1, (int) round($seconds / 60)));
								}
								?>
								<tr>
									<td class="text-nowrap"><?php echo HTMLHelper::_('date', $run->started_at, Text::_('DATE_FORMAT_LC5'), $siteTimezone); ?></td>
									<td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
									<td>
										<?php if ($run->newsletter_id) : ?>
											<a href="<?php echo Route::_(AdministratorRoute::newsletter((int) $run->newsletter_id)); ?>"><?php echo htmlspecialchars((string) ($run->newsletter_title ?: ('#' . (int) $run->newsletter_id)), ENT_QUOTES, 'UTF-8'); ?></a>
											<?php $currentStatus = $newsletterStatus($run->newsletter_status !== null ? (int) $run->newsletter_status : null); ?>
											<?php if ($currentStatus !== '') : ?><div class="small text-muted"><?php echo htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
										<?php else : ?>—<?php endif; ?>
									</td>
									<td><?php echo (int) $run->item_count; ?></td>
									<td class="text-nowrap"><?php echo htmlspecialchars($duration, ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="small"><?php echo trim((string) ($run->message ?? '')) !== '' ? htmlspecialchars((string) $run->message, ENT_QUOTES, 'UTF-8') : '—'; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<input type="hidden" name="id" value="<?php echo (int) ($item->id ?? 0); ?>">
	<input type="hidden" name="state" value="<?php echo (int) ($item->state ?? 1); ?>">
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>
document.addEventListener('DOMContentLoaded', function ()
{
	const summary = document.getElementById('pm-digest-audience-summary');
	const warning = document.getElementById('pm-digest-audience-warning');
	const includeAll = document.querySelector('.pm-digest-audience-all');
	const topics = Array.from(document.querySelectorAll('.pm-digest-audience-topic'));
	const groups = Array.from(document.querySelectorAll('.pm-digest-audience-group'));
	const cutoff = document.getElementById('digest-cutoff');
	const rollingPeriod = document.getElementById('pm-digest-rolling-period');
	const generationMode = document.getElementById('digest-mode');
	const autoConfirm = document.getElementById('pm-digest-auto-confirm');
	const checkedLabels = function (items)
	{
		return items.filter(function (item)
		{
			return item.checked;
		}).map(function (item)
		{
			const label = document.querySelector('label[for="' + item.id + '"]');

			return label ? label.textContent.trim() : '';
		}).filter(Boolean);
	};
	const updateConditionalFields = function ()
	{
		if (rollingPeriod)
		{
			rollingPeriod.hidden = !cutoff || cutoff.value !== 'rolling';
		}

		if (autoConfirm)
		{
			autoConfirm.hidden = !generationMode || generationMode.value !== 'auto';
		}
	};
	const update = function ()
	{
		if (!summary)
		{
			return;
		}

		const selectedTopics = checkedLabels(topics);
		const selectedGroups = checkedLabels(groups);
		const parts = [];

		if (includeAll && includeAll.checked)
		{
			parts.push(summary.dataset.all || '');
		}
		else if (selectedTopics.length > 0)
		{
			parts.push((summary.dataset.topics || '').replace('{names}', selectedTopics.join(', ')));
		}

		if (selectedGroups.length > 0)
		{
			parts.push((summary.dataset.groups || '').replace('{names}', selectedGroups.join(', ')));
		}

		summary.textContent = parts.length > 0 ? parts.join(' ') : (summary.dataset.none || '');
		summary.classList.toggle('alert-danger', parts.length === 0);
		summary.classList.toggle('alert-info', parts.length > 0);

		if (warning)
		{
			warning.hidden = !(includeAll && includeAll.checked && selectedTopics.length > 0);
		}
	};

	[cutoff, generationMode].filter(Boolean).forEach(function (field)
	{
		field.addEventListener('change', updateConditionalFields);
	});
	updateConditionalFields();

	[includeAll, ...topics, ...groups].filter(Boolean).forEach(function (check)
	{
		check.addEventListener('change', update);
	});
	update();
});
</script>
