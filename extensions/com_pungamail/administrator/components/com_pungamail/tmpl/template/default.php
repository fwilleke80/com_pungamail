<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Template
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

/** @var \Punga\Component\PungaMail\Administrator\View\Template\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Helper\MarkdownEditorHelper;

$item = $this->item;
$newContentOverride = trim((string) ($item->new_content_item_template ?? ''));
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
?>
<style>
.pm-new-content-override > summary { list-style: none; }
.pm-new-content-override > summary::-webkit-details-marker { display: none; }
.pm-new-content-override .pm-collapse-indicator { display: inline-block; transition: transform .15s ease; }
.pm-new-content-override[open] .pm-collapse-indicator { transform: rotate(90deg); }
</style>
<div class="container-fluid">
	<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post" name="adminForm" id="adminForm" data-pm-unsaved-warning="1">
		<input type="hidden" name="id" value="<?php echo (int) ($item->id ?? 0); ?>">
		<div class="row g-4 align-items-start">
			<div class="col-12 col-xl-9">
				<div class="card mb-3">
					<div class="card-body">
						<label class="form-label" for="pt-title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label>
						<input class="form-control" id="pt-title" required name="title" value="<?php echo htmlspecialchars((string) ($item->title ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
				</div>
				<?php echo HTMLHelper::_('uitab.startTabSet', 'pm-template-tabs', ['active' => 'pm-template-mail-content', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'pm-template-tabs', 'pm-template-mail-content', Text::_('COM_PUNGAMAIL_TAB_MAIL_CONTENT')); ?>
		<div class="pt-3">
			<div class="card mb-3">
				<div class="card-body">
					<div class="mb-3">
						<label class="form-label" for="pt-subject"><?php echo Text::_('COM_PUNGAMAIL_EMAIL_SUBJECT'); ?></label>
						<input class="form-control" id="pt-subject" name="subject" value="<?php echo htmlspecialchars((string) ($item->subject ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<div>
						<label class="form-label" for="pt-body"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER_BODY_MARKDOWN'); ?></label>
						<?php echo MarkdownEditorHelper::render(
							'body_markdown',
							'pt-body',
							(string) ($item->body_markdown ?? ''),
							18,
							['{recipient}', '{new_content}'],
							[Text::_('COM_PUNGAMAIL_NEWSLETTER_PLACEHOLDER_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_IMAGE_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_TABLE_HELP')]
						); ?>
					</div>
				</div>
			</div>

			<details class="card mb-3 pm-new-content-override">
				<summary class="card-header d-flex align-items-center gap-2" style="cursor:pointer">
					<span class="pm-collapse-indicator" aria-hidden="true">▶</span>
					<strong><?php echo Text::_('COM_PUNGAMAIL_NEW_CONTENT_LAYOUT'); ?></strong>
					<?php if ($newContentOverride !== '') : ?>
						<span class="badge bg-info text-dark"><?php echo Text::_('COM_PUNGAMAIL_CUSTOM_OVERRIDE_ACTIVE'); ?></span>
					<?php endif; ?>
				</summary>
				<div class="card-body">
					<label class="form-label" for="pt-new-content-template"><?php echo Text::_('COM_PUNGAMAIL_NEW_CONTENT_ITEM_TEMPLATE'); ?></label>
					<?php echo MarkdownEditorHelper::render(
						'new_content_item_template',
						'pt-new-content-template',
						$newContentOverride,
						7,
						['{title}', '{title_link}', '{publish_date}', '{excerpt}', '{read_more}', '{url}', '{content_type}'],
						[Text::_('COM_PUNGAMAIL_NEW_CONTENT_ITEM_TEMPLATE_OVERRIDE_DESC'), Text::_('COM_PUNGAMAIL_NEW_CONTENT_ITEM_TEMPLATE_PLACEHOLDER_HELP')]
					); ?>
				</div>
			</details>

		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'pm-template-tabs', 'pm-template-design', Text::_('COM_PUNGAMAIL_TAB_DESIGN')); ?>
		<div class="pt-3">
			<div class="card mb-3">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_MAIL_STYLE_OVERRIDES'); ?></strong></div>
				<div class="card-body">
					<p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_STYLE_TEMPLATE_HELP'); ?></p>
					<div class="row g-3">
						<?php foreach ($styleFields as $key => $label) : ?>
							<div class="col-md-6">
								<label class="form-label" for="pts-<?php echo $key; ?>"><?php echo Text::_($label); ?></label>
								<input class="form-control" id="pts-<?php echo $key; ?>" name="style[<?php echo $key; ?>]" value="<?php echo htmlspecialchars((string) ($style[$key] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_INHERIT'), ENT_QUOTES, 'UTF-8'); ?>">
							</div>
						<?php endforeach; ?>
						<div class="col-12">
							<label class="form-label" for="pt-css"><?php echo Text::_('COM_PUNGAMAIL_STYLE_CUSTOM_CSS'); ?></label>
							<textarea class="form-control font-monospace" id="pt-css" name="custom_css" rows="6"><?php echo htmlspecialchars((string) ($item->custom_css ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
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
					<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_TEMPLATE_SETTINGS'); ?></strong></div>
					<div class="card-body">
						<div class="mb-3">
							<label class="form-label" for="heading-mode"><?php echo Text::_('COM_PUNGAMAIL_MAIL_BODY_HEADING'); ?></label>
							<select class="form-select" id="heading-mode" name="heading_mode">
								<option value="inherit" <?php echo (string) ($item->heading_mode ?? 'inherit') === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
								<option value="custom" <?php echo (string) ($item->heading_mode ?? '') === 'custom' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CUSTOM'); ?></option>
								<option value="site" <?php echo (string) ($item->heading_mode ?? '') === 'site' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_USE_SITE_NAME'); ?></option>
								<option value="none" <?php echo (string) ($item->heading_mode ?? '') === 'none' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_NO_HEADING'); ?></option>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label" for="mail-heading"><?php echo Text::_('COM_PUNGAMAIL_CUSTOM_HEADING'); ?></label>
							<input class="form-control" id="mail-heading" name="mail_heading" value="<?php echo htmlspecialchars((string) ($item->mail_heading ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
						</div>
						<div class="mb-3">
							<label class="form-label" for="browser-view"><?php echo Text::_('COM_PUNGAMAIL_BROWSER_VIEW'); ?></label>
							<select class="form-select" id="browser-view" name="browser_view">
								<option value="-1" <?php echo (int) ($item->browser_view ?? -1) === -1 ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
								<option value="1" <?php echo (int) ($item->browser_view ?? -1) === 1 ? 'selected' : ''; ?>><?php echo Text::_('JENABLED'); ?></option>
								<option value="0" <?php echo (int) ($item->browser_view ?? -1) === 0 ? 'selected' : ''; ?>><?php echo Text::_('JDISABLED'); ?></option>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label" for="reply-mode"><?php echo Text::_('COM_PUNGAMAIL_REPLY_TO'); ?></label>
							<select class="form-select" id="reply-mode" name="reply_to_mode">
								<option value="inherit" <?php echo (string) ($item->reply_to_mode ?? 'inherit') === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
								<option value="custom" <?php echo (string) ($item->reply_to_mode ?? '') === 'custom' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CUSTOM'); ?></option>
								<option value="none" <?php echo (string) ($item->reply_to_mode ?? '') === 'none' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_REPLY_TO_NONE'); ?></option>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label" for="reply-email"><?php echo Text::_('COM_PUNGAMAIL_REPLY_TO_EMAIL'); ?></label>
							<input class="form-control" type="email" id="reply-email" name="reply_to_email" value="<?php echo htmlspecialchars((string) ($item->reply_to_email ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
						</div>
						<div>
							<label class="form-label" for="reply-name"><?php echo Text::_('COM_PUNGAMAIL_REPLY_TO_NAME'); ?></label>
							<input class="form-control" id="reply-name" name="reply_to_name" value="<?php echo htmlspecialchars((string) ($item->reply_to_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
						</div>
					</div>
				</div>
			</aside>
		</div>
		<input type="hidden" name="task" value="">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
