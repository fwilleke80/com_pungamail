<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Newsletter\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Helper\MarkdownEditorHelper;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRenderer;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRepository;

$item = $this->item;
$isDraft = $item === null || in_array((int) $item->status, [NewsletterRepository::STATUS_DRAFT, NewsletterRepository::STATUS_SCHEDULED], true);
$selectedByKey = [];
foreach ($this->selectedItems as $selected)
{
	$selectedByKey[(string) $selected->source_key . "\0" . (string) $selected->source_item_id] = $selected;
}
$cutoffDateValue = $this->contentCutoffStart ? substr((string) $this->contentCutoffStart, 0, 10) : '';
$cutoffDisplay = $cutoffDateValue !== '' ? HTMLHelper::_('date', $cutoffDateValue, Text::_('DATE_FORMAT_LC4')) : Text::_('COM_PUNGAMAIL_BEGINNING');
$style = $this->styleOverrides;
$styleFields = [
	'content_width' => 'COM_PUNGAMAIL_STYLE_CONTENT_WIDTH',
	'outer_background' => 'COM_PUNGAMAIL_STYLE_OUTER_BACKGROUND',
	'content_background' => 'COM_PUNGAMAIL_STYLE_CONTENT_BACKGROUND',
	'text_color' => 'COM_PUNGAMAIL_STYLE_TEXT_COLOR',
	'heading_color' => 'COM_PUNGAMAIL_STYLE_HEADING_COLOR',
	'heading_background' => 'COM_PUNGAMAIL_STYLE_HEADING_BACKGROUND',
	'mail_heading_color' => 'COM_PUNGAMAIL_STYLE_MAIL_HEADING_COLOR',
	'link_color' => 'COM_PUNGAMAIL_STYLE_LINK_COLOR',
	'font_family' => 'COM_PUNGAMAIL_STYLE_FONT_FAMILY',
	'font_size' => 'COM_PUNGAMAIL_STYLE_FONT_SIZE',
	'content_padding' => 'COM_PUNGAMAIL_STYLE_CONTENT_PADDING',
	'logo_url' => 'COM_PUNGAMAIL_STYLE_LOGO_URL',
	'logo_width' => 'COM_PUNGAMAIL_STYLE_LOGO_WIDTH',
	'footer_color' => 'COM_PUNGAMAIL_STYLE_FOOTER_COLOR',
];
$siteTimezone = (string) Factory::getApplication()->get('offset', 'UTC');
$statusKey = match ((int) ($item->status ?? NewsletterRepository::STATUS_DRAFT))
{
	NewsletterRepository::STATUS_DRAFT => 'COM_PUNGAMAIL_STATUS_DRAFT',
	NewsletterRepository::STATUS_QUEUED => 'COM_PUNGAMAIL_STATUS_QUEUED',
	NewsletterRepository::STATUS_SENDING => 'COM_PUNGAMAIL_STATUS_SENDING',
	NewsletterRepository::STATUS_SENT => 'COM_PUNGAMAIL_STATUS_SENT',
	NewsletterRepository::STATUS_SENT_WITH_FAILURES => 'COM_PUNGAMAIL_STATUS_SENT_WITH_FAILURES',
	NewsletterRepository::STATUS_SCHEDULED => 'COM_PUNGAMAIL_STATUS_SCHEDULED',
	NewsletterRepository::STATUS_FAILED => 'COM_PUNGAMAIL_STATUS_FAILED',
	NewsletterRepository::STATUS_CANCELLED => 'COM_PUNGAMAIL_STATUS_CANCELLED',
	default => 'COM_PUNGAMAIL_STATUS_UNKNOWN',
};
$scheduledInputValue = '';

if ($item !== null && !empty($item->scheduled_at))
{
	$scheduledInputValue = HTMLHelper::_('date', (string) $item->scheduled_at, 'Y-m-d\TH:i', $siteTimezone);
}
?>
<style>
.pm-content-row[draggable="true"] { cursor: grab; }
.pm-content-row[draggable="true"]:active { cursor: grabbing; }
.pm-content-drag { display: inline-flex; align-items: center; gap: .35rem; }
.pm-content-row.pm-content-dragging { opacity: .55; }
.pm-content-drop-marker { min-height: 2rem; display: flex; align-items: center; justify-content: center; }
</style>
<div class="container-fluid">
<?php if (!$isDraft && $item !== null) : ?>
	<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post" name="adminForm" id="adminForm">
		<input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
		<input type="hidden" name="task" value="">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<div class="card mb-3"><div class="card-body">
		<h2 class="h5 mb-2"><?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?></h2>
		<p><strong><?php echo Text::_('COM_PUNGAMAIL_SUBJECT'); ?>:</strong> <?php echo htmlspecialchars((string) ($this->snapshotPreview['subject'] ?? $item->snapshot_subject), ENT_QUOTES, 'UTF-8'); ?></p>
		<p><strong><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?>:</strong> <?php echo (int) $item->recipient_count; ?> &nbsp; <strong><?php echo Text::_('COM_PUNGAMAIL_SENT'); ?>:</strong> <?php echo (int) $item->sent_count; ?> &nbsp; <strong><?php echo Text::_('COM_PUNGAMAIL_FAILED'); ?>:</strong> <?php echo (int) $item->failed_count; ?></p>
	</div></div>
	<?php if ($this->statistics !== []) : ?><div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DELIVERY_STATISTICS'); ?></strong></div><div class="card-body"><div class="row g-3"><?php foreach ($this->statistics as $key => $value) : ?><div class="col-6 col-md-3"><div class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_STAT_' . strtoupper($key)); ?></div><div class="fs-4"><?php echo (int) $value; ?></div></div><?php endforeach; ?></div><p class="small text-muted mt-3 mb-0"><?php echo Text::_('COM_PUNGAMAIL_TRANSPORT_ACCEPTED_HELP'); ?></p></div></div><?php endif; ?>
	<?php if ($item->snapshot_html) : ?>
		<?php $sentPreview = str_replace(['href="' . NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER . '"', 'href="' . NewsletterRenderer::BROWSER_PLACEHOLDER . '"'], ['aria-disabled="true"', 'aria-disabled="true"'], (string) ($this->snapshotPreview['html'] ?? $item->snapshot_html)); ?>
		<iframe sandbox="" title="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SENT_NEWSLETTER'), ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:700px;border:1px solid #ccc;background:#fff" srcdoc="<?php echo htmlspecialchars($sentPreview, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
	<?php endif; ?>
	<div class="card mt-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_FROZEN_RECIPIENTS'); ?></strong></div><div class="card-body p-0">
		<table class="table table-striped mb-0"><thead><tr><th><?php echo Text::_('JGLOBAL_NAME'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_EMAIL'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SOURCE'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_ATTEMPTS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SENT'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_ERROR'); ?></th></tr></thead><tbody>
		<?php foreach ($this->queueRecipients as $recipient) : ?>
		<tr><td><?php echo htmlspecialchars((string) (($recipient->recipient_name ?? '') !== '' ? $recipient->recipient_name : $recipient->email), ENT_QUOTES, 'UTF-8'); ?></td><td><code><?php echo htmlspecialchars((string) $recipient->email, ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars((string) $recipient->source, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) $recipient->status, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $recipient->attempts; ?></td><td><?php echo $recipient->sent_at ? HTMLHelper::_('date', $recipient->sent_at, Text::_('DATE_FORMAT_LC5'), $siteTimezone) : '—'; ?></td><td class="small text-danger"><?php echo htmlspecialchars((string) ($recipient->last_error ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div></div>
	<form class="mt-3 d-flex flex-wrap gap-2" action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post"><input type="hidden" name="id" value="<?php echo (int) $item->id; ?>"><?php if (in_array((int) $item->status, [NewsletterRepository::STATUS_QUEUED, NewsletterRepository::STATUS_SENDING], true)) : ?><input type="hidden" name="paused" value="<?php echo (int) $item->queue_paused === 1 ? 0 : 1; ?>"><button class="btn btn-outline-warning" formaction="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.toggleMailingPause'); ?>" type="submit"><?php echo Text::_((int) $item->queue_paused === 1 ? 'COM_PUNGAMAIL_RESUME_MAILING' : 'COM_PUNGAMAIL_PAUSE_MAILING'); ?></button><button class="btn btn-danger" formaction="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.cancelRemaining'); ?>" type="submit" onclick="return confirm('<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_CANCEL_REMAINING_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>');"><?php echo Text::_('COM_PUNGAMAIL_CANCEL_REMAINING'); ?></button><?php endif; ?><a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletters'); ?>"><?php echo Text::_('JTOOLBAR_BACK'); ?></a><?php echo HTMLHelper::_('form.token'); ?></form>
<?php else : ?>
	<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post" name="adminForm" id="adminForm" data-pm-unsaved-warning="1">
		<input type="hidden" name="id" value="<?php echo (int) ($item->id ?? 0); ?>">
		<?php if ($item !== null && (int) $item->status === NewsletterRepository::STATUS_SCHEDULED) : ?><div class="alert alert-info"><?php echo Text::sprintf('COM_PUNGAMAIL_EDITING_SCHEDULED', HTMLHelper::_('date', $item->scheduled_at, Text::_('DATE_FORMAT_LC5'), $siteTimezone)); ?></div><?php endif; ?>
		<div class="row g-4 align-items-start">
			<div class="col-12 col-xl-9">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'pm-newsletter-tabs', ['active' => 'pm-settings', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'pm-newsletter-tabs', 'pm-settings', Text::_('COM_PUNGAMAIL_TAB_SETTINGS')); ?>
		<div class="row g-3 pt-3">
			<div class="col-12 col-xl-8">
				<div class="card mb-3">
					<div class="card-body">
						<div class="mb-3">
							<label class="form-label" for="pm-title"><?php echo Text::_('COM_PUNGAMAIL_INTERNAL_TITLE'); ?></label>
							<input class="form-control" id="pm-title" name="title" required value="<?php echo htmlspecialchars((string) ($item->title ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
						</div>
					</div>
				</div>

				<div class="card mb-3">
					<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_MESSAGE_OPTIONS'); ?></strong></div>
					<div class="card-body">
						<div class="row g-3">
							<div class="col-md-6">
								<label class="form-label" for="heading-mode"><?php echo Text::_('COM_PUNGAMAIL_MAIL_BODY_HEADING'); ?></label>
								<select class="form-select" id="heading-mode" name="heading_mode">
									<option value="inherit" <?php echo (string) ($item->heading_mode ?? 'inherit') === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
									<option value="custom" <?php echo (string) ($item->heading_mode ?? '') === 'custom' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CUSTOM'); ?></option>
									<option value="site" <?php echo (string) ($item->heading_mode ?? '') === 'site' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_USE_SITE_NAME'); ?></option>
									<option value="none" <?php echo (string) ($item->heading_mode ?? '') === 'none' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_NO_HEADING'); ?></option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="mail-heading"><?php echo Text::_('COM_PUNGAMAIL_CUSTOM_HEADING'); ?></label>
								<input class="form-control" id="mail-heading" name="mail_heading" value="<?php echo htmlspecialchars((string) ($item->mail_heading ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="browser-view"><?php echo Text::_('COM_PUNGAMAIL_BROWSER_VIEW'); ?></label>
								<select class="form-select" id="browser-view" name="browser_view">
									<option value="-1" <?php echo (int) ($item->browser_view ?? -1) === -1 ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
									<option value="1" <?php echo (int) ($item->browser_view ?? -1) === 1 ? 'selected' : ''; ?>><?php echo Text::_('JENABLED'); ?></option>
									<option value="0" <?php echo (int) ($item->browser_view ?? -1) === 0 ? 'selected' : ''; ?>><?php echo Text::_('JDISABLED'); ?></option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="reply-mode"><?php echo Text::_('COM_PUNGAMAIL_REPLY_TO'); ?></label>
								<select class="form-select" id="reply-mode" name="reply_to_mode">
									<option value="inherit" <?php echo (string) ($item->reply_to_mode ?? 'inherit') === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
									<option value="custom" <?php echo (string) ($item->reply_to_mode ?? '') === 'custom' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CUSTOM'); ?></option>
									<option value="none" <?php echo (string) ($item->reply_to_mode ?? '') === 'none' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_REPLY_TO_NONE'); ?></option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="reply-email"><?php echo Text::_('COM_PUNGAMAIL_REPLY_TO_EMAIL'); ?></label>
								<input class="form-control" type="email" id="reply-email" name="reply_to_email" value="<?php echo htmlspecialchars((string) ($item->reply_to_email ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="reply-name"><?php echo Text::_('COM_PUNGAMAIL_REPLY_TO_NAME'); ?></label>
								<input class="form-control" id="reply-name" name="reply_to_name" value="<?php echo htmlspecialchars((string) ($item->reply_to_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-12 col-xl-4">
				<div class="card mb-3">
					<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_AUDIENCE'); ?></strong></div>
					<div class="card-body">
						<p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_AUDIENCE_UNION_HELP'); ?></p>
						<div id="pm-audience-summary" class="alert alert-info small" role="status"
							data-none="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_NONE'), ENT_QUOTES, 'UTF-8'); ?>"
							data-all="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_ALL'), ENT_QUOTES, 'UTF-8'); ?>"
							data-topics="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_TOPICS'), ENT_QUOTES, 'UTF-8'); ?>"
							data-groups="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_GROUPS'), ENT_QUOTES, 'UTF-8'); ?>"></div>
						<div id="pm-audience-all-warning" class="alert alert-warning small" hidden><?php echo Text::_('COM_PUNGAMAIL_AUDIENCE_ALL_TOPICS_WARNING'); ?></div>
						<div class="form-check mb-3">
							<input type="hidden" name="include_subscribers" value="0">
							<input class="form-check-input pm-audience-all" type="checkbox" name="include_subscribers" value="1" id="include-subscribers" <?php echo $item !== null && (int) $item->include_subscribers === 1 ? 'checked' : ''; ?>>
							<label class="form-check-label fw-semibold" for="include-subscribers"><?php echo Text::_('COM_PUNGAMAIL_ALL_CONFIRMED_SUBSCRIBERS'); ?></label>
							<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_ALL_SUBSCRIBERS_HELP'); ?></div>
						</div>
						<fieldset>
							<legend class="h6 mb-1"><?php echo Text::_('COM_PUNGAMAIL_TOPICS'); ?></legend>
							<div class="small text-muted mb-2"><?php echo Text::_('COM_PUNGAMAIL_TOPICS_TARGET_HELP'); ?></div>
							<?php foreach ($this->topics as $topic) : ?>
								<div class="form-check"><input class="form-check-input pm-audience-topic" type="checkbox" name="topic_ids[]" value="<?php echo (int) $topic->id; ?>" id="topic-<?php echo (int) $topic->id; ?>" <?php echo in_array((int) $topic->id, $this->selectedTopicIds, true) ? 'checked' : ''; ?>><label class="form-check-label" for="topic-<?php echo (int) $topic->id; ?>"><?php echo htmlspecialchars((string) $topic->title, ENT_QUOTES, 'UTF-8'); ?></label></div>
							<?php endforeach; ?>
						</fieldset>
						<fieldset class="mt-3">
							<legend class="h6 mb-1"><?php echo Text::_('COM_PUNGAMAIL_ADDITIONAL_USER_GROUPS'); ?></legend>
							<div class="small text-muted mb-2"><?php echo Text::_('COM_PUNGAMAIL_GROUPS_RESPECT_OPT_OUT'); ?></div>
							<?php foreach ($this->userGroups as $group) : ?>
								<div class="form-check"><input class="form-check-input pm-audience-group" type="checkbox" name="group_ids[]" value="<?php echo (int) $group->id; ?>" id="group-<?php echo (int) $group->id; ?>" <?php echo in_array((int) $group->id, $this->selectedGroupIds, true) ? 'checked' : ''; ?>><label class="form-check-label" for="group-<?php echo (int) $group->id; ?>"><?php echo htmlspecialchars((string) $group->title, ENT_QUOTES, 'UTF-8'); ?></label></div>
							<?php endforeach; ?>
						</fieldset>
					</div>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'pm-newsletter-tabs', 'pm-mail-content', Text::_('COM_PUNGAMAIL_TAB_MAIL_CONTENT')); ?>
		<div class="pt-3">
			<div class="card mb-3">
				<div class="card-body">
					<div class="row g-2 align-items-end mb-3">
						<div class="col-md">
							<label class="form-label" for="pm-template"><?php echo Text::_('COM_PUNGAMAIL_TEMPLATE'); ?></label>
							<select class="form-select" id="pm-template" name="template_id">
								<option value="0"><?php echo Text::_('COM_PUNGAMAIL_NO_TEMPLATE'); ?></option>
								<?php foreach ($this->templates as $template) : ?>
									<option value="<?php echo (int) $template->id; ?>" <?php echo (int) ($item->template_id ?? 0) === (int) $template->id ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $template->title, ENT_QUOTES, 'UTF-8'); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-auto">
							<button class="btn btn-outline-secondary" type="button" onclick="Joomla.submitbutton('newsletter.applyTemplate');"><?php echo Text::_('COM_PUNGAMAIL_APPLY_TEMPLATE'); ?></button>
						</div>
					</div>
					<div class="form-text mb-3"><?php echo Text::_('COM_PUNGAMAIL_TEMPLATE_COPY_HELP'); ?></div>
					<div class="mb-3">
						<label class="form-label" for="pm-subject"><?php echo Text::_('COM_PUNGAMAIL_EMAIL_SUBJECT'); ?></label>
						<input class="form-control" id="pm-subject" name="subject" required value="<?php echo htmlspecialchars((string) ($item->subject ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<div>
						<label class="form-label" for="pm-body"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER_BODY_MARKDOWN'); ?></label>
						<?php echo MarkdownEditorHelper::render(
							'body_markdown',
							'pm-body',
							(string) ($item->body_markdown ?? ''),
							18,
							['{recipient}', '{new_content}'],
							[Text::_('COM_PUNGAMAIL_NEWSLETTER_PLACEHOLDER_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_IMAGE_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_TABLE_HELP')]
						); ?>
					</div>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'pm-newsletter-tabs', 'pm-content-selection', Text::_('COM_PUNGAMAIL_TAB_CONTENT_SELECTION')); ?>
		<div class="pt-3">
			<div class="card mb-3">
				<div class="card-header"><strong><?php echo Text::sprintf('COM_PUNGAMAIL_NEW_CONTENT_SINCE', $cutoffDisplay); ?></strong></div>
				<div class="card-body border-bottom">
					<div class="row g-3 align-items-end">
						<div class="col-sm-5">
							<label class="form-label" for="pm-content-cutoff"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_PUBLISHED_SINCE'); ?></label>
							<input class="form-control" id="pm-content-cutoff" type="date" name="content_cutoff_start" value="<?php echo htmlspecialchars($cutoffDateValue, ENT_QUOTES, 'UTF-8'); ?>">
						</div>
						<div class="col-auto">
							<button class="btn btn-outline-secondary" type="button" onclick="Joomla.submitbutton('newsletter.applyCutoff');"><?php echo Text::_('COM_PUNGAMAIL_APPLY_FILTERS'); ?></button>
						</div>
					</div>
					<div class="form-text mb-3"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_DATE_HELP'); ?></div>
					<div class="fw-semibold mb-2"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_TYPES'); ?></div>
					<div class="d-flex flex-wrap gap-3">
						<?php foreach ($this->contentTypes as $key => $type) : ?>
							<div class="form-check"><input class="form-check-input" type="checkbox" name="source_keys[]" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" id="source-<?php echo sha1($key); ?>" <?php echo in_array($key, $this->selectedSourceKeys, true) ? 'checked' : ''; ?>><label class="form-check-label" for="source-<?php echo sha1($key); ?>"><?php echo htmlspecialchars((string) $type->label, ENT_QUOTES, 'UTF-8'); ?></label></div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="card-body border-bottom">
					<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
						<div>
							<div class="fw-semibold"><?php echo Text::_('COM_PUNGAMAIL_SELECTED_CONTENT'); ?> <span class="badge text-bg-secondary" id="pm-selected-count">0</span></div>
							<div class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_SELECTED_CONTENT_HELP'); ?></div>
						</div>
						<button class="btn btn-outline-secondary btn-sm" type="button" id="pm-clear-selected"><?php echo Text::_('COM_PUNGAMAIL_CLEAR_SELECTED'); ?></button>
					</div>
					<div id="pm-selected-content-list" data-drop-label="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_DROP_CONTENT_HERE'), ENT_QUOTES, 'UTF-8'); ?>"></div>
					<div class="text-muted small py-2" id="pm-selected-empty"><?php echo Text::_('COM_PUNGAMAIL_NO_SELECTED_CONTENT'); ?></div>
				</div>

				<div class="card-body border-bottom">
					<div class="fw-semibold mb-2"><?php echo Text::_('COM_PUNGAMAIL_AVAILABLE_CONTENT'); ?></div>
					<div class="row g-2 align-items-end">
						<div class="col-md-7">
							<label class="form-label" for="pm-content-search"><?php echo Text::_('JSEARCH_FILTER'); ?></label>
							<input type="search" class="form-control" id="pm-content-search" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_CONTENT_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>">
						</div>
						<div class="col-md-3">
							<label class="form-label" for="pm-content-sort"><?php echo Text::_('COM_PUNGAMAIL_AVAILABLE_CONTENT_SORT'); ?></label>
							<select class="form-select" id="pm-content-sort">
								<option value="newest"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_ORDER_NEWEST'); ?></option>
								<option value="oldest"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_ORDER_OLDEST'); ?></option>
								<option value="title"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_SORT_TITLE'); ?></option>
							</select>
						</div>
						<div class="col-md-2 d-grid">
							<button class="btn btn-outline-secondary" type="button" id="pm-select-visible"><?php echo Text::_('COM_PUNGAMAIL_SELECT_VISIBLE'); ?></button>
						</div>
					</div>
				</div>

				<div id="pm-available-content-list" style="max-height:38rem;overflow-y:auto;overscroll-behavior:contain">
					<?php if ($this->availableContent === []) : ?><p class="text-muted m-3"><?php echo Text::_('COM_PUNGAMAIL_NO_NEW_CONTENT'); ?></p><?php endif; ?>
				</div>

				<?php
				$selectedRows = [];
				$availableRows = [];
				foreach ($this->availableContent as $content)
				{
					$key = (string) $content->source_key . "\0" . (string) $content->id;
					$selected = $selectedByKey[$key] ?? null;

					if ($selected)
					{
						$selectedRows[] = [$content, $selected];
					}
					else
					{
						$availableRows[] = [$content, $selected];
					}
				}
				usort($selectedRows, static fn (array $a, array $b): int => (int) $a[1]->ordering <=> (int) $b[1]->ordering);
				$renderRows = static function (array $rows, bool $isSelected): void
				{
					foreach ($rows as [$content, $selected])
					{
						$key = (string) $content->source_key . "\0" . (string) $content->id;
						$token = sha1($key);
						$searchText = mb_strtolower((string) $content->source_label . ' ' . (string) $content->title, 'UTF-8');
						$publishedSort = strtotime((string) ($content->published ?? '')) ?: 0;
						?>
						<div class="border-bottom p-3 pm-content-row" data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>" data-title="<?php echo htmlspecialchars(mb_strtolower((string) $content->title, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>" data-published="<?php echo (int) $publishedSort; ?>" draggable="<?php echo $isSelected ? 'true' : 'false'; ?>">
							<input type="hidden" name="item_source[<?php echo $token; ?>]" value="<?php echo htmlspecialchars((string) $content->source_key, ENT_QUOTES, 'UTF-8'); ?>">
							<input type="hidden" name="item_id[<?php echo $token; ?>]" value="<?php echo htmlspecialchars((string) $content->id, ENT_QUOTES, 'UTF-8'); ?>">
							<input type="hidden" class="pm-content-ordering" name="item_ordering[<?php echo $token; ?>]" value="<?php echo (int) ($selected->ordering ?? 0); ?>">
							<div class="d-flex gap-2 align-items-start">
								<div class="form-check flex-grow-1 mb-2"><input class="form-check-input pm-content-check" type="checkbox" name="selected_items[]" value="<?php echo $token; ?>" id="content-<?php echo $token; ?>" <?php echo $isSelected ? 'checked' : ''; ?>><label class="form-check-label fw-semibold" for="content-<?php echo $token; ?>"><?php if (trim((string) ($content->url ?? '')) !== '') : ?><a href="<?php echo htmlspecialchars((string) $content->url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_OPEN_CONTENT_NEW_TAB'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $content->title, ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo htmlspecialchars((string) $content->title, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></label></div>
								<span class="pm-content-drag small text-muted" <?php echo $isSelected ? '' : 'hidden'; ?> title="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_DRAG_TO_REORDER'), ENT_QUOTES, 'UTF-8'); ?>"><span class="fa fa-grip-vertical" aria-hidden="true"></span><?php echo Text::_('COM_PUNGAMAIL_DRAG_TO_REORDER'); ?></span>
							</div>
							<div class="small text-muted mb-2"><span class="badge text-bg-secondary me-2"><?php echo htmlspecialchars((string) $content->source_label, ENT_QUOTES, 'UTF-8'); ?></span><?php echo htmlspecialchars((string) $content->published, ENT_QUOTES, 'UTF-8'); ?></div>
							<div class="pm-content-selection-options" <?php echo $isSelected ? '' : 'hidden'; ?>>
								<div class="row g-2"><div class="col-12"><label class="form-label small"><?php echo Text::_('COM_PUNGAMAIL_TITLE_OVERRIDE'); ?></label><input class="form-control form-control-sm" name="title_override[<?php echo $token; ?>]" value="<?php echo htmlspecialchars((string) ($selected->title_override ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div><div class="col-12"><label class="form-label small"><?php echo Text::_('COM_PUNGAMAIL_EXCERPT_OVERRIDE'); ?></label><textarea class="form-control form-control-sm" rows="2" name="excerpt_override[<?php echo $token; ?>]"><?php echo htmlspecialchars((string) ($selected->excerpt_override ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
							</div>
						</div>
						<?php
					}
				};
				?>
				<div id="pm-content-row-staging" hidden>
					<?php $renderRows($selectedRows, true); $renderRows($availableRows, false); ?>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'pm-newsletter-tabs', 'pm-design', Text::_('COM_PUNGAMAIL_TAB_DESIGN')); ?>
		<div class="pt-3">
			<div class="card mb-3">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_MAIL_STYLE_OVERRIDES'); ?></strong></div>
				<div class="card-body">
					<p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_STYLE_INHERIT_HELP'); ?></p>
					<div class="row g-3">
						<?php foreach ($styleFields as $styleKey => $labelKey) : ?>
							<div class="col-md-6">
								<label class="form-label" for="style-<?php echo $styleKey; ?>"><?php echo Text::_($labelKey); ?></label>
								<input class="form-control" id="style-<?php echo $styleKey; ?>" name="style[<?php echo $styleKey; ?>]" value="<?php echo htmlspecialchars((string) ($style[$styleKey] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_INHERIT'), ENT_QUOTES, 'UTF-8'); ?>">
							</div>
						<?php endforeach; ?>
						<div class="col-12">
							<label class="form-label" for="pm-custom-css"><?php echo Text::_('COM_PUNGAMAIL_STYLE_CUSTOM_CSS'); ?></label>
							<textarea class="form-control font-monospace" id="pm-custom-css" name="custom_css" rows="8"><?php echo htmlspecialchars((string) ($item->custom_css ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
							<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_STYLE_CUSTOM_CSS_DESC'); ?></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
			</div>
			<aside class="col-12 col-xl-3">
				<div class="card mb-3">
					<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER_DETAILS'); ?></strong></div>
					<div class="card-body">
						<div class="mb-3">
							<div class="small text-muted"><?php echo Text::_('JSTATUS'); ?></div>
							<div class="fw-semibold"><?php echo Text::_($statusKey); ?><?php if ($item !== null && (int) $item->state === 2) : ?> <span class="badge bg-secondary ms-1"><?php echo Text::_('COM_PUNGAMAIL_ARCHIVED_NOTE'); ?></span><?php endif; ?></div>
						</div>
					</div>
				</div>
				<div class="card mb-3">
					<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_SCHEDULE_SEND'); ?></strong></div>
					<div class="card-body">
						<label class="form-label" for="pm-scheduled-at"><?php echo Text::_('COM_PUNGAMAIL_SCHEDULE_DATE'); ?></label>
						<input class="form-control" type="datetime-local" id="pm-scheduled-at" name="scheduled_at" value="<?php echo htmlspecialchars($scheduledInputValue, ENT_QUOTES, 'UTF-8'); ?>">
						<div class="form-text mb-3"><?php echo Text::_('COM_PUNGAMAIL_SCHEDULE_SIDEBAR_HELP'); ?></div>
						<button class="btn btn-primary w-100" type="button" onclick="Joomla.submitbutton('newsletter.schedule');"><?php echo Text::_($item !== null && (int) $item->status === NewsletterRepository::STATUS_SCHEDULED ? 'COM_PUNGAMAIL_RESCHEDULE_SEND' : 'COM_PUNGAMAIL_SCHEDULE_SEND'); ?></button>
						<?php if ($item !== null && (int) $item->status === NewsletterRepository::STATUS_SCHEDULED) : ?>
							<button class="btn btn-outline-danger w-100 mt-2" type="button" onclick="Joomla.submitbutton('newsletter.cancelScheduled');"><?php echo Text::_('COM_PUNGAMAIL_CANCEL_SCHEDULE'); ?></button>
						<?php endif; ?>
					</div>
				</div>
			</aside>
		</div>
		<input type="hidden" name="schedule_from_editor" value="1">
		<input type="hidden" name="task" value="">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
			<script>
		document.addEventListener('DOMContentLoaded', function ()
		{
			const form = document.getElementById('adminForm');
			const search = document.getElementById('pm-content-search');
			const sort = document.getElementById('pm-content-sort');
			const selectedList = document.getElementById('pm-selected-content-list');
			const availableList = document.getElementById('pm-available-content-list');
			const staging = document.getElementById('pm-content-row-staging');
			const selectedEmpty = document.getElementById('pm-selected-empty');
			const count = document.getElementById('pm-selected-count');
			const selectVisible = document.getElementById('pm-select-visible');
			const clearSelected = document.getElementById('pm-clear-selected');
			const audienceSummary = document.getElementById('pm-audience-summary');
			const audienceWarning = document.getElementById('pm-audience-all-warning');
			const audienceAll = document.querySelector('.pm-audience-all');
			const audienceTopics = Array.from(document.querySelectorAll('.pm-audience-topic'));
			const audienceGroups = Array.from(document.querySelectorAll('.pm-audience-group'));
			let draggedRow = null;
			const dropMarker = document.createElement('div');
			dropMarker.className = 'pm-content-drop-marker alert alert-info py-1 my-1 text-center small';
			dropMarker.textContent = selectedList ? String(selectedList.dataset.dropLabel || '') : '';

			if (staging && selectedList && availableList)
			{
				Array.from(staging.querySelectorAll('.pm-content-row')).forEach(function (row)
				{
					const check = row.querySelector('.pm-content-check');
					(check && check.checked ? selectedList : availableList).appendChild(row);
				});
				staging.remove();
			}

			const availableRows = function ()
			{
				return availableList ? Array.from(availableList.querySelectorAll(':scope > .pm-content-row')) : [];
			};
			const selectedRows = function ()
			{
				return selectedList ? Array.from(selectedList.querySelectorAll(':scope > .pm-content-row')) : [];
			};
			const updateSelected = function (notifyChange = false)
			{
				const rows = selectedRows();
				rows.forEach(function (row, index)
				{
					row.hidden = false;
					row.draggable = true;
					const ordering = row.querySelector('.pm-content-ordering');
					const options = row.querySelector('.pm-content-selection-options');
					const drag = row.querySelector('.pm-content-drag');

					if (ordering)
					{
						ordering.value = String(index);
					}

					if (options)
					{
						options.hidden = false;
					}

					if (drag)
					{
						drag.hidden = false;
					}
				});

				if (count)
				{
					count.textContent = String(rows.length);
				}

				if (selectedEmpty)
				{
					selectedEmpty.hidden = rows.length > 0;
				}

				if (clearSelected)
				{
					clearSelected.disabled = rows.length === 0;
				}

				if (notifyChange && form)
				{
					form.dispatchEvent(new Event('change', {bubbles: true}));
				}
			};
			const prepareAvailableRow = function (row)
			{
				row.draggable = false;
				const options = row.querySelector('.pm-content-selection-options');
				const drag = row.querySelector('.pm-content-drag');

				if (options)
				{
					options.hidden = true;
				}

				if (drag)
				{
					drag.hidden = true;
				}
			};
			const applySearch = function ()
			{
				const term = search ? search.value.trim().toLocaleLowerCase() : '';
				availableRows().forEach(function (row)
				{
					row.hidden = term !== '' && !String(row.dataset.search || '').includes(term);
				});
			};
			const sortAvailable = function ()
			{
				if (!availableList)
				{
					return;
				}

				const mode = sort ? sort.value : 'newest';
				availableRows().sort(function (a, b)
				{
					if (mode === 'title')
					{
						return String(a.dataset.title || '').localeCompare(String(b.dataset.title || ''));
					}

					const aDate = Number(a.dataset.published || 0);
					const bDate = Number(b.dataset.published || 0);

					return mode === 'oldest' ? aDate - bDate : bDate - aDate;
				}).forEach(function (row)
				{
					availableList.appendChild(row);
					prepareAvailableRow(row);
				});
				applySearch();
			};
			const moveForSelection = function (check)
			{
				const row = check.closest('.pm-content-row');

				if (!row || !selectedList || !availableList)
				{
					return;
				}

				if (check.checked)
				{
					selectedList.appendChild(row);
				}
				else
				{
					availableList.appendChild(row);
					prepareAvailableRow(row);
				}

				updateSelected();
				sortAvailable();
			};
			const checkedLabels = function (items)
			{
				return items.filter(function (item)
				{
					return item.checked;
				}).map(function (item)
				{
					const label = document.querySelector(`label[for="${item.id}"]`);

					return label ? label.textContent.trim() : '';
				}).filter(Boolean);
			};
			const updateAudience = function ()
			{
				if (!audienceSummary)
				{
					return;
				}

				const topics = checkedLabels(audienceTopics);
				const groups = checkedLabels(audienceGroups);
				const parts = [];

				if (audienceAll && audienceAll.checked)
				{
					parts.push(audienceSummary.dataset.all || '');
				}
				else if (topics.length > 0)
				{
					parts.push((audienceSummary.dataset.topics || '').replace('{names}', topics.join(', ')));
				}

				if (groups.length > 0)
				{
					parts.push((audienceSummary.dataset.groups || '').replace('{names}', groups.join(', ')));
				}

				audienceSummary.textContent = parts.length > 0 ? parts.join(' ') : (audienceSummary.dataset.none || '');
				audienceSummary.classList.toggle('alert-danger', parts.length === 0);
				audienceSummary.classList.toggle('alert-info', parts.length > 0);

				if (audienceWarning)
				{
					audienceWarning.hidden = !(audienceAll && audienceAll.checked && topics.length > 0);
				}
			};

			document.querySelectorAll('.pm-content-check').forEach(function (check)
			{
				check.addEventListener('change', function ()
				{
					moveForSelection(check);
				});
			});

			if (search)
			{
				search.addEventListener('input', applySearch);
			}

			if (sort)
			{
				sort.addEventListener('change', sortAvailable);
			}

			if (selectVisible)
			{
				selectVisible.addEventListener('click', function ()
				{
					availableRows().filter(function (row)
					{
						return !row.hidden;
					}).forEach(function (row)
					{
						const check = row.querySelector('.pm-content-check');
						if (check && !check.checked)
						{
							check.checked = true;
							check.dispatchEvent(new Event('change', {bubbles: true}));
						}
					});
				});
			}

			if (clearSelected)
			{
				clearSelected.addEventListener('click', function ()
				{
					selectedRows().forEach(function (row)
					{
						const check = row.querySelector('.pm-content-check');
						if (check && check.checked)
						{
							check.checked = false;
							check.dispatchEvent(new Event('change', {bubbles: true}));
						}
					});
				});
			}

			if (selectedList)
			{
				const dragAfterElement = function (pointerY)
				{
					const candidates = selectedRows().filter(function (row)
					{
						return row !== draggedRow;
					});
					let closest = {offset: Number.NEGATIVE_INFINITY, element: null};

					candidates.forEach(function (row)
					{
						const rectangle = row.getBoundingClientRect();
						const offset = pointerY - rectangle.top - rectangle.height / 2;

						if (offset < 0 && offset > closest.offset)
						{
							closest = {offset: offset, element: row};
						}
					});

					return closest.element;
				};
				const clearDropMarker = function ()
				{
					if (dropMarker.parentElement)
					{
						dropMarker.remove();
					}
				};

				selectedList.addEventListener('dragstart', function (event)
				{
					draggedRow = event.target.closest('.pm-content-row');

					if (draggedRow && event.dataTransfer)
					{
						draggedRow.classList.add('pm-content-dragging');
						event.dataTransfer.effectAllowed = 'move';
					}
				});
				selectedList.addEventListener('dragover', function (event)
				{
					if (!draggedRow)
					{
						return;
					}

					event.preventDefault();
					const after = dragAfterElement(event.clientY);

					if (after)
					{
						selectedList.insertBefore(dropMarker, after);
					}
					else
					{
						selectedList.appendChild(dropMarker);
					}
				});
				selectedList.addEventListener('drop', function (event)
				{
					if (!draggedRow)
					{
						return;
					}

					event.preventDefault();

					if (dropMarker.parentElement === selectedList)
					{
						selectedList.insertBefore(draggedRow, dropMarker);
					}

					clearDropMarker();
					updateSelected(true);
				});
				selectedList.addEventListener('dragend', function ()
				{
					if (draggedRow)
					{
						draggedRow.classList.remove('pm-content-dragging');
					}

					clearDropMarker();
					draggedRow = null;
				});
			}

			[audienceAll, ...audienceTopics, ...audienceGroups].filter(Boolean).forEach(function (check)
			{
				check.addEventListener('change', updateAudience);
			});

			updateSelected();
			sortAvailable();
			updateAudience();
		});
		</script>
<?php endif; ?>
</div>
