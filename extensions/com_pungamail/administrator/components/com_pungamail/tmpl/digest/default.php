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
$filterDateControlTemplate = HTMLHelper::_('calendar', '', '__PM_FILTER_NAME__', '__PM_FILTER_ID__', '%Y-%m-%d', [
	'class' => 'form-control form-control-sm',
	'showTime' => false,
	'todayBtn' => true,
	'singleHeader' => true,
]);
$filterDateTimeControlTemplate = HTMLHelper::_('calendar', '', '__PM_FILTER_NAME__', '__PM_FILTER_ID__', '%Y-%m-%d %H:%M:%S', [
	'class' => 'form-control form-control-sm',
	'showTime' => true,
	'timeFormat' => 24,
	'todayBtn' => true,
	'singleHeader' => true,
]);

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
					<div class="small text-muted mb-3"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_FILTERS_HELP'); ?></div>
					<?php foreach ($this->contentTypes as $key => $type) : ?>
						<?php $selected = in_array((string) $key, $this->sourceKeys, true); $sourceId = md5((string) $key); ?>
						<div class="border rounded p-3 mb-3 pm-digest-source-card" data-source-key="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>">
							<div class="d-flex justify-content-between align-items-center gap-3">
								<div class="form-check m-0">
									<input class="form-check-input pm-digest-source-toggle" type="checkbox" name="source_keys[]" value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>" id="digest-source-<?php echo $sourceId; ?>" <?php echo $selected ? 'checked' : ''; ?>>
									<label class="form-check-label fw-semibold" for="digest-source-<?php echo $sourceId; ?>"><?php echo htmlspecialchars((string) $type->label, ENT_QUOTES, 'UTF-8'); ?></label>
								</div>
								<span class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_SOURCE_FILTER_SUMMARY'); ?></span>
							</div>
							<div class="pm-digest-source-config mt-3" <?php echo $selected ? '' : 'hidden'; ?>>
								<div class="small fw-semibold mb-2"><?php echo Text::_('COM_PUNGAMAIL_FILTERS'); ?></div>
								<div class="pm-digest-filter-list"></div>
								<div class="d-flex flex-wrap gap-2 mt-2">
									<button class="btn btn-sm btn-outline-secondary pm-add-filter" type="button"><?php echo Text::_('COM_PUNGAMAIL_ADD_FILTER'); ?></button>
									<button class="btn btn-sm btn-outline-primary pm-preview-source" type="button"><?php echo Text::_('COM_PUNGAMAIL_PREVIEW_MATCHING_CONTENT'); ?></button>
								</div>
								<div class="pm-source-preview mt-2" aria-live="polite"></div>
							</div>
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
						<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_CUTOFF_SINCE_LAST_HELP'); ?></div>
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
const pmDigestFilterFields = <?php echo json_encode($this->filterFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const pmDigestFilters = <?php echo json_encode($this->filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const pmDigestFilterControls = <?php echo json_encode([
	'date' => $filterDateControlTemplate,
	'datetime' => $filterDateTimeControlTemplate,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const pmDigestFilterText = <?php echo json_encode([
	'field' => Text::_('COM_PUNGAMAIL_FILTER_FIELD'),
	'operator' => Text::_('COM_PUNGAMAIL_FILTER_OPERATOR'),
	'value' => Text::_('COM_PUNGAMAIL_FILTER_VALUE'),
	'remove' => Text::_('JACTION_DELETE'),
	'none' => Text::_('COM_PUNGAMAIL_NO_FILTERS'),
	'loading' => Text::_('COM_PUNGAMAIL_PREVIEW_LOADING'),
	'previewCount' => Text::_('COM_PUNGAMAIL_PREVIEW_MATCH_COUNT'),
	'previewBlocked' => Text::_('COM_PUNGAMAIL_PREVIEW_BLOCKED_COUNT'),
	'previewMore' => Text::_('COM_PUNGAMAIL_PREVIEW_MORE_ITEMS'),
	'previewError' => Text::_('COM_PUNGAMAIL_PREVIEW_ERROR'),
	'operators' => [
		'eq' => Text::_('COM_PUNGAMAIL_FILTER_EQ'), 'neq' => Text::_('COM_PUNGAMAIL_FILTER_NEQ'),
		'in' => Text::_('COM_PUNGAMAIL_FILTER_IN'), 'not_in' => Text::_('COM_PUNGAMAIL_FILTER_NOT_IN'),
		'contains' => Text::_('COM_PUNGAMAIL_FILTER_CONTAINS'), 'not_contains' => Text::_('COM_PUNGAMAIL_FILTER_NOT_CONTAINS'),
		'gt' => Text::_('COM_PUNGAMAIL_FILTER_GT'), 'gte' => Text::_('COM_PUNGAMAIL_FILTER_GTE'),
		'lt' => Text::_('COM_PUNGAMAIL_FILTER_LT'), 'lte' => Text::_('COM_PUNGAMAIL_FILTER_LTE'),
		'is_empty' => Text::_('COM_PUNGAMAIL_FILTER_IS_EMPTY'), 'not_empty' => Text::_('COM_PUNGAMAIL_FILTER_NOT_EMPTY'),
	],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
document.addEventListener('DOMContentLoaded', function ()
{
	const form = document.getElementById('adminForm');
	const filterIndexes = {};
	const operatorSet = function (field)
	{
		if (field && field.kind === 'boolean') return ['eq', 'neq'];
		if (field && Array.isArray(field.options) && field.options.length) return ['in', 'not_in', 'eq', 'neq', 'is_empty', 'not_empty'];
		if (field && (field.kind === 'number' || field.kind === 'date')) return ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'in', 'not_in', 'is_empty', 'not_empty'];
		return ['eq', 'neq', 'contains', 'not_contains', 'in', 'not_in', 'is_empty', 'not_empty'];
	};
	const fieldByName = function (sourceKey, name)
	{
		return (pmDigestFilterFields[sourceKey] || []).find(function (field) { return field.name === name; }) || null;
	};
	const makeOption = function (value, label, selected)
	{
		const option = document.createElement('option');
		option.value = value;
		option.textContent = label;
		option.selected = selected;
		return option;
	};
	const fieldLabel = function (field)
	{
		const type = String(field?.data_type || '').trim();
		return type === '' ? field.label : field.label + ' — ' + type;
	};
	const calendarControl = function (holder, kind, name, id, value)
	{
		const html = String(pmDigestFilterControls[kind] || '')
			.replaceAll('__PM_FILTER_NAME__', name)
			.replaceAll('__PM_FILTER_ID__', id);
		const template = document.createElement('template');
		template.innerHTML = html.trim();
		const fragment = template.content.cloneNode(true);
		const input = fragment.querySelector('input');
		if (input)
		{
			const current = String(value == null ? '' : value).trim();
			input.value = current;
			input.setAttribute('data-alt-value', current);
		}
		holder.appendChild(fragment);
		holder.dispatchEvent(new CustomEvent('joomla:updated', {bubbles: true}));
	};
	const renderValue = function (holder, sourceKey, index, field, operator, value)
	{
		holder.textContent = '';
		if (operator === 'is_empty' || operator === 'not_empty') return;
		const values = Array.isArray(value) ? value.map(String) : String(value == null ? '' : value).split(',').map(function (entry) { return entry.trim(); }).filter(Boolean);
		if (field && Array.isArray(field.options) && field.options.length)
		{
			const select = document.createElement('select');
			select.className = 'form-select form-select-sm';
			select.name = 'filters[' + sourceKey + '][' + index + '][value][]';
			select.multiple = operator === 'in' || operator === 'not_in';
			field.options.forEach(function (option) { select.appendChild(makeOption(String(option.value), option.label, values.includes(String(option.value)))); });
			holder.appendChild(select);
			return;
		}

		const inputName = 'filters[' + sourceKey + '][' + index + '][value]';
		const inputId = 'pm-digest-filter-' + index + '-' + String(sourceKey).replace(/[^A-Za-z0-9_-]/g, '-') + '-value';
		const scalarValue = Array.isArray(value) ? value.join(', ') : String(value == null ? '' : value);
		const listOperator = operator === 'in' || operator === 'not_in';

		if (field && !listOperator && (field.input_kind === 'date' || field.input_kind === 'datetime'))
		{
			calendarControl(holder, field.input_kind, inputName, inputId, scalarValue);
			return;
		}

		const input = document.createElement('input');
		input.className = 'form-control form-control-sm';
		input.name = inputName;
		input.id = inputId;
		input.value = scalarValue;

		if (field && !listOperator && field.input_kind === 'number')
		{
			input.type = 'number';
			input.step = field.number_step || 'any';
			if (field.number_min !== null && field.number_min !== undefined && field.number_min !== '') input.min = String(field.number_min);
		}
		else if (field && !listOperator && field.input_kind === 'time')
		{
			// Joomla's standard Time form field renders a native HTML time input.
			input.type = 'time';
			input.step = '1';
		}
		else
		{
			input.type = 'text';
			input.placeholder = listOperator ? 'value1, value2' : '';
			if (field && field.input_kind === 'number') input.inputMode = 'decimal';
		}

		holder.appendChild(input);
	};
	const addFilterRow = function (card, rule)
	{
		const sourceKey = card.dataset.sourceKey;
		const fields = pmDigestFilterFields[sourceKey] || [];
		if (!fields.length) return;
		filterIndexes[sourceKey] = (filterIndexes[sourceKey] || 0) + 1;
		const index = filterIndexes[sourceKey];
		const row = document.createElement('div');
		row.className = 'row g-2 align-items-end mb-2 pm-digest-filter-row';
		const fieldCol = document.createElement('div'); fieldCol.className = 'col-md-4';
		const fieldSelect = document.createElement('select'); fieldSelect.className = 'form-select form-select-sm'; fieldSelect.name = 'filters[' + sourceKey + '][' + index + '][field]';
		fields.forEach(function (field) { fieldSelect.appendChild(makeOption(field.name, fieldLabel(field), field.name === (rule.field || fields[0].name))); });
		fieldCol.appendChild(fieldSelect);
		const opCol = document.createElement('div'); opCol.className = 'col-md-3';
		const opSelect = document.createElement('select'); opSelect.className = 'form-select form-select-sm'; opSelect.name = 'filters[' + sourceKey + '][' + index + '][operator]'; opCol.appendChild(opSelect);
		const valueCol = document.createElement('div'); valueCol.className = 'col-md-4 pm-filter-value';
		const removeCol = document.createElement('div'); removeCol.className = 'col-md-1 d-grid';
		const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-sm btn-outline-danger'; remove.title = pmDigestFilterText.remove; remove.textContent = '×'; remove.addEventListener('click', function () { row.remove(); }); removeCol.appendChild(remove);
		row.append(fieldCol, opCol, valueCol, removeCol);
		const updateOperator = function (preferred)
		{
			const field = fieldByName(sourceKey, fieldSelect.value);
			const operators = operatorSet(field); opSelect.textContent = '';
			operators.forEach(function (operator) { opSelect.appendChild(makeOption(operator, pmDigestFilterText.operators[operator] || operator, operator === preferred)); });
			if (!operators.includes(preferred)) opSelect.value = operators[0];
			renderValue(valueCol, sourceKey, index, field, opSelect.value, rule.value || '');
		};
		fieldSelect.addEventListener('change', function () { rule.value = ''; updateOperator('eq'); });
		opSelect.addEventListener('change', function () { renderValue(valueCol, sourceKey, index, fieldByName(sourceKey, fieldSelect.value), opSelect.value, ''); });
		updateOperator(rule.operator || ((fieldByName(sourceKey, fieldSelect.value)?.options || []).length ? 'in' : 'eq'));
		card.querySelector('.pm-digest-filter-list').appendChild(row);
	};
	const updateSourceCard = function (card)
	{
		const toggle = card.querySelector('.pm-digest-source-toggle');
		const config = card.querySelector('.pm-digest-source-config');
		if (config) config.hidden = !toggle.checked;
	};
	document.querySelectorAll('.pm-digest-source-card').forEach(function (card)
	{
		const sourceKey = card.dataset.sourceKey;
		(pmDigestFilters[sourceKey] || []).forEach(function (rule) { addFilterRow(card, rule); });
		card.querySelector('.pm-digest-source-toggle').addEventListener('change', function () { updateSourceCard(card); });
		card.querySelector('.pm-add-filter').addEventListener('click', function () { addFilterRow(card, {}); });
		card.querySelector('.pm-preview-source').addEventListener('click', async function (event)
		{
			const output = card.querySelector('.pm-source-preview'); output.className = 'pm-source-preview mt-2 small text-muted'; output.textContent = pmDigestFilterText.loading;
			try
			{
				const data = new FormData(form); data.set('task', 'digest.previewSource'); data.set('preview_source_key', sourceKey);
				const response = await fetch(form.action, {method: 'POST', body: data, headers: {'Accept': 'application/json'}}); const payload = await response.json();
				if (!response.ok || payload.success === false) throw new Error(payload.message || pmDigestFilterText.previewError);
				const result = payload.data || payload; output.textContent = ''; output.className = 'pm-source-preview mt-2 small';
				const summaryLine = document.createElement('div'); summaryLine.className = 'fw-semibold'; summaryLine.textContent = pmDigestFilterText.previewCount.replace('%d', String(result.count)); output.appendChild(summaryLine);
				if (result.blocked_count > 0) { const blocked = document.createElement('div'); blocked.className = 'text-muted'; blocked.textContent = pmDigestFilterText.previewBlocked.replace('%d', String(result.blocked_count)); output.appendChild(blocked); }
				if (Array.isArray(result.items) && result.items.length) { const list = document.createElement('ul'); list.className = 'mb-0 mt-1'; result.items.forEach(function (item) { const li = document.createElement('li'); li.textContent = item.title + (item.published ? ' — ' + item.published : ''); list.appendChild(li); }); output.appendChild(list); }
				if (result.count > (result.items || []).length) { const more = document.createElement('div'); more.className = 'text-muted'; more.textContent = pmDigestFilterText.previewMore.replace('%d', String(result.count - result.items.length)); output.appendChild(more); }
			}
			catch (error) { output.className = 'pm-source-preview mt-2 small text-danger'; output.textContent = error.message || pmDigestFilterText.previewError; }
		});
		updateSourceCard(card);
	});
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
