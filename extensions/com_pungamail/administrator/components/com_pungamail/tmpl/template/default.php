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
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

$item = $this->item;
$mailPlaceholders = array_merge(['{recipient}', '{new_content}'], ServiceFactory::userFields()->placeholders());
$style = $this->styleOverrides;
?>
<?php echo \Joomla\CMS\Layout\LayoutHelper::render('pungamail.section_navigation', ['section' => 'design', 'active' => 'templates'], JPATH_ADMINISTRATOR . '/components/com_pungamail/layouts'); ?>
<style>
</style>
<div class="container-fluid">
	<form action="<?php echo Route::_(AdministratorRoute::template((int) ($item->id ?? 0))); ?>" method="post" name="adminForm" id="adminForm" data-pm-unsaved-warning="1">
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
							$mailPlaceholders,
							[Text::_('COM_PUNGAMAIL_NEWSLETTER_PLACEHOLDER_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_IMAGE_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_TABLE_HELP')]
						); ?>
					</div>
				</div>
			</div>

		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'pm-template-tabs', 'pm-template-layout', Text::_('COM_PUNGAMAIL_TAB_LAYOUT')); ?>
		<div class="pt-3">
			<?php echo \Joomla\CMS\Layout\LayoutHelper::render(
				'pungamail.mail_layout_overrides',
				[
					'item' => $item,
					'style' => $style,
					'prefix' => 'template-layout',
					'help_key' => 'COM_PUNGAMAIL_LAYOUT_TEMPLATE_HELP',
				],
				JPATH_ADMINISTRATOR . '/components/com_pungamail/layouts'
			); ?>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
			</div>
			<aside class="col-12 col-xl-3">
				<div class="card mb-3">
					<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_TEMPLATE_SETTINGS'); ?></strong></div>
					<div class="card-body">
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
