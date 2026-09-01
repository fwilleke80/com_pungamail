<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Newsletter\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRenderer;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRepository;

$item = $this->item;
$isDraft = $item === null || (int) $item->status === NewsletterRepository::STATUS_DRAFT;
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
	'link_color' => 'COM_PUNGAMAIL_STYLE_LINK_COLOR',
	'font_family' => 'COM_PUNGAMAIL_STYLE_FONT_FAMILY',
	'font_size' => 'COM_PUNGAMAIL_STYLE_FONT_SIZE',
	'content_padding' => 'COM_PUNGAMAIL_STYLE_CONTENT_PADDING',
	'logo_url' => 'COM_PUNGAMAIL_STYLE_LOGO_URL',
	'logo_width' => 'COM_PUNGAMAIL_STYLE_LOGO_WIDTH',
	'footer_color' => 'COM_PUNGAMAIL_STYLE_FOOTER_COLOR',
];
?>
<div class="container-fluid">
<?php if (!$isDraft && $item !== null) : ?>
	<div class="card mb-3"><div class="card-body">
		<h2 class="h5 mb-2"><?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?></h2>
		<p><strong><?php echo Text::_('COM_PUNGAMAIL_SUBJECT'); ?>:</strong> <?php echo htmlspecialchars((string) ($this->snapshotPreview['subject'] ?? $item->snapshot_subject), ENT_QUOTES, 'UTF-8'); ?></p>
		<p><strong><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?>:</strong> <?php echo (int) $item->recipient_count; ?> &nbsp; <strong><?php echo Text::_('COM_PUNGAMAIL_SENT'); ?>:</strong> <?php echo (int) $item->sent_count; ?> &nbsp; <strong><?php echo Text::_('COM_PUNGAMAIL_FAILED'); ?>:</strong> <?php echo (int) $item->failed_count; ?></p>
	</div></div>
	<?php if ($item->snapshot_html) : ?>
		<?php $sentPreview = str_replace('href="' . NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER . '"', 'aria-disabled="true"', (string) ($this->snapshotPreview['html'] ?? $item->snapshot_html)); ?>
		<iframe sandbox="" title="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SENT_NEWSLETTER'), ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:700px;border:1px solid #ccc;background:#fff" srcdoc="<?php echo htmlspecialchars($sentPreview, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
	<?php endif; ?>
	<div class="card mt-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_FROZEN_RECIPIENTS'); ?></strong></div><div class="card-body p-0">
		<table class="table table-striped mb-0"><thead><tr><th><?php echo Text::_('JGLOBAL_NAME'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_EMAIL'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SOURCE'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_ATTEMPTS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SENT'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_ERROR'); ?></th></tr></thead><tbody>
		<?php foreach ($this->queueRecipients as $recipient) : ?>
		<tr><td><?php echo htmlspecialchars((string) (($recipient->recipient_name ?? '') !== '' ? $recipient->recipient_name : $recipient->email), ENT_QUOTES, 'UTF-8'); ?></td><td><code><?php echo htmlspecialchars((string) $recipient->email, ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars((string) $recipient->source, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) $recipient->status, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $recipient->attempts; ?></td><td><?php echo $recipient->sent_at ? HTMLHelper::_('date', $recipient->sent_at, Text::_('DATE_FORMAT_LC5'), 'UTC') : '—'; ?></td><td class="small text-danger"><?php echo htmlspecialchars((string) ($recipient->last_error ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div></div>
	<form class="mt-3" action="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.duplicate'); ?>" method="post"><input type="hidden" name="id" value="<?php echo (int) $item->id; ?>"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_DUPLICATE_AS_DRAFT'); ?></button> <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletters'); ?>"><?php echo Text::_('JTOOLBAR_BACK'); ?></a><?php echo HTMLHelper::_('form.token'); ?></form>
<?php else : ?>
	<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post" name="adminForm" id="adminForm">
		<input type="hidden" name="id" value="<?php echo (int) ($item->id ?? 0); ?>">
		<div class="row g-3">
			<div class="col-12 col-xl-8">
				<div class="card mb-3"><div class="card-body">
					<div class="mb-3"><label class="form-label" for="pm-title"><?php echo Text::_('COM_PUNGAMAIL_INTERNAL_TITLE'); ?></label><input class="form-control" id="pm-title" name="title" required value="<?php echo htmlspecialchars((string) ($item->title ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
					<div class="mb-3"><label class="form-label" for="pm-template"><?php echo Text::_('COM_PUNGAMAIL_TEMPLATE'); ?></label><div class="input-group"><select class="form-select" id="pm-template" name="template_id"><option value="0"><?php echo Text::_('COM_PUNGAMAIL_NO_TEMPLATE'); ?></option><?php foreach ($this->templates as $template) : ?><option value="<?php echo (int) $template->id; ?>" <?php echo (int) ($item->template_id ?? 0) === (int) $template->id ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $template->title, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select><button class="btn btn-outline-secondary" type="button" onclick="Joomla.submitbutton('newsletter.applyTemplate');"><?php echo Text::_('COM_PUNGAMAIL_APPLY_TEMPLATE'); ?></button></div><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_TEMPLATE_COPY_HELP'); ?></div></div>
					<div class="mb-3"><label class="form-label" for="pm-subject"><?php echo Text::_('COM_PUNGAMAIL_EMAIL_SUBJECT'); ?></label><input class="form-control" id="pm-subject" name="subject" required value="<?php echo htmlspecialchars((string) ($item->subject ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
					<div><label class="form-label" for="pm-body"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER_BODY_MARKDOWN'); ?></label><textarea class="form-control font-monospace" id="pm-body" name="body_markdown" rows="16"><?php echo htmlspecialchars((string) ($item->body_markdown ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MARKDOWN_HELP'); ?></div><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER_PLACEHOLDER_HELP'); ?></div><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MARKDOWN_IMAGE_HELP'); ?></div><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MARKDOWN_TABLE_HELP'); ?></div></div>
				</div></div>

				<div class="card mb-3">
					<div class="card-header"><strong><?php echo Text::sprintf('COM_PUNGAMAIL_NEW_CONTENT_SINCE', $cutoffDisplay); ?></strong></div>
					<div class="card-body border-bottom">
						<div class="row g-3 align-items-end"><div class="col-sm-5"><label class="form-label" for="pm-content-cutoff"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_PUBLISHED_SINCE'); ?></label><input class="form-control" id="pm-content-cutoff" type="date" name="content_cutoff_start" value="<?php echo htmlspecialchars($cutoffDateValue, ENT_QUOTES, 'UTF-8'); ?>"></div><div class="col-auto"><button class="btn btn-outline-secondary" type="button" onclick="Joomla.submitbutton('newsletter.applyCutoff');"><?php echo Text::_('COM_PUNGAMAIL_APPLY_FILTERS'); ?></button></div></div>
						<div class="form-text mb-3"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_DATE_HELP'); ?></div>
						<div class="fw-semibold mb-2"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_TYPES'); ?></div>
						<div class="d-flex flex-wrap gap-3 mb-3"><?php foreach ($this->contentTypes as $key => $type) : ?><div class="form-check"><input class="form-check-input" type="checkbox" name="source_keys[]" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" id="source-<?php echo sha1($key); ?>" <?php echo in_array($key, $this->selectedSourceKeys, true) ? 'checked' : ''; ?>><label class="form-check-label" for="source-<?php echo sha1($key); ?>"><?php echo htmlspecialchars((string) $type->label, ENT_QUOTES, 'UTF-8'); ?></label></div><?php endforeach; ?></div>
						<label class="form-label" for="pm-content-search"><?php echo Text::_('JSEARCH_FILTER'); ?></label><input type="search" class="form-control" id="pm-content-search" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_CONTENT_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<div id="pm-content-list" style="max-height:38rem;overflow-y:auto;overscroll-behavior:contain">
					<?php if ($this->availableContent === []) : ?><p class="text-muted m-3"><?php echo Text::_('COM_PUNGAMAIL_NO_NEW_CONTENT'); ?></p><?php endif; ?>
					<?php $defaultOrder = 0; foreach ($this->availableContent as $content) :
						$key = (string) $content->source_key . "\0" . (string) $content->id;
						$selected = $selectedByKey[$key] ?? null;
						$token = sha1($key);
						$search = mb_strtolower((string) $content->source_label . ' ' . (string) $content->title, 'UTF-8');
					?>
					<div class="border-bottom p-3 pm-content-row" data-search="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
						<input type="hidden" name="item_source[<?php echo $token; ?>]" value="<?php echo htmlspecialchars((string) $content->source_key, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="item_id[<?php echo $token; ?>]" value="<?php echo htmlspecialchars((string) $content->id, ENT_QUOTES, 'UTF-8'); ?>">
						<div class="form-check mb-2"><input class="form-check-input pm-content-check" type="checkbox" name="selected_items[]" value="<?php echo $token; ?>" id="content-<?php echo $token; ?>" <?php echo $selected ? 'checked' : ''; ?>><label class="form-check-label fw-semibold" for="content-<?php echo $token; ?>"><?php echo htmlspecialchars((string) $content->title, ENT_QUOTES, 'UTF-8'); ?></label></div>
						<div class="small text-muted mb-2"><span class="badge text-bg-secondary me-2"><?php echo htmlspecialchars((string) $content->source_label, ENT_QUOTES, 'UTF-8'); ?></span><?php echo htmlspecialchars((string) $content->published, ENT_QUOTES, 'UTF-8'); ?></div>
						<div class="row g-2"><div class="col-md-2"><label class="form-label small"><?php echo Text::_('JGRID_HEADING_ORDERING'); ?></label><input type="number" class="form-control form-control-sm" name="item_ordering[<?php echo $token; ?>]" value="<?php echo (int) ($selected->ordering ?? $defaultOrder++); ?>"></div><div class="col-md-10"><label class="form-label small"><?php echo Text::_('COM_PUNGAMAIL_TITLE_OVERRIDE'); ?></label><input class="form-control form-control-sm" name="title_override[<?php echo $token; ?>]" value="<?php echo htmlspecialchars((string) ($selected->title_override ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div><div class="col-12"><label class="form-label small"><?php echo Text::_('COM_PUNGAMAIL_EXCERPT_OVERRIDE'); ?></label><textarea class="form-control form-control-sm" rows="2" name="excerpt_override[<?php echo $token; ?>]"><?php echo htmlspecialchars((string) ($selected->excerpt_override ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
					</div>
					<?php endforeach; ?>
					</div>
					<div class="card-footer small text-muted"><span id="pm-selected-count">0</span> <?php echo Text::_('COM_PUNGAMAIL_SELECTED_ITEMS'); ?></div>
				</div>

				<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_MAIL_STYLE_OVERRIDES'); ?></strong></div><div class="card-body"><p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_STYLE_INHERIT_HELP'); ?></p><div class="row g-3">
				<?php foreach ($styleFields as $styleKey => $labelKey) : ?><div class="col-md-6"><label class="form-label" for="style-<?php echo $styleKey; ?>"><?php echo Text::_($labelKey); ?></label><input class="form-control" id="style-<?php echo $styleKey; ?>" name="style[<?php echo $styleKey; ?>]" value="<?php echo htmlspecialchars((string) ($style[$styleKey] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_INHERIT'), ENT_QUOTES, 'UTF-8'); ?>"></div><?php endforeach; ?>
				<div class="col-12"><label class="form-label" for="pm-custom-css"><?php echo Text::_('COM_PUNGAMAIL_STYLE_CUSTOM_CSS'); ?></label><textarea class="form-control font-monospace" id="pm-custom-css" name="custom_css" rows="6"><?php echo htmlspecialchars((string) ($item->custom_css ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_STYLE_CUSTOM_CSS_DESC'); ?></div></div>
				</div></div></div>
			</div>

			<div class="col-12 col-xl-4">
				<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?></strong></div><div class="card-body"><div class="form-check mb-3"><input type="hidden" name="include_subscribers" value="0"><input class="form-check-input" type="checkbox" name="include_subscribers" value="1" id="include-subscribers" <?php echo $item === null || (int) $item->include_subscribers === 1 ? 'checked' : ''; ?>><label class="form-check-label" for="include-subscribers"><?php echo Text::_('COM_PUNGAMAIL_ALL_CONFIRMED_SUBSCRIBERS'); ?></label></div><div class="fw-semibold mb-2"><?php echo Text::_('COM_PUNGAMAIL_ADDITIONAL_USER_GROUPS'); ?></div><div class="small text-muted mb-2"><?php echo Text::_('COM_PUNGAMAIL_GROUPS_RESPECT_OPT_OUT'); ?></div><?php foreach ($this->userGroups as $group) : ?><div class="form-check"><input class="form-check-input" type="checkbox" name="group_ids[]" value="<?php echo (int) $group->id; ?>" id="group-<?php echo (int) $group->id; ?>" <?php echo in_array((int) $group->id, $this->selectedGroupIds, true) ? 'checked' : ''; ?>><label class="form-check-label" for="group-<?php echo (int) $group->id; ?>"><?php echo htmlspecialchars((string) $group->title, ENT_QUOTES, 'UTF-8'); ?></label></div><?php endforeach; ?></div></div>
			</div>
		</div>
		<input type="hidden" name="task" value="">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const search = document.getElementById('pm-content-search');
		const rows = Array.from(document.querySelectorAll('.pm-content-row'));
		const checks = Array.from(document.querySelectorAll('.pm-content-check'));
		const count = document.getElementById('pm-selected-count');
		const updateCount = () => { if (count) count.textContent = String(checks.filter((item) => item.checked).length); };
		if (search) search.addEventListener('input', () => { const term = search.value.trim().toLocaleLowerCase(); rows.forEach((row) => { row.hidden = term !== '' && !String(row.dataset.search || '').includes(term); }); });
		checks.forEach((check) => check.addEventListener('change', updateCount));
		updateCount();
	});
	</script>
<?php endif; ?>
</div>
