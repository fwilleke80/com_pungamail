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

$item = $this->item;
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
	<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post" name="adminForm" id="adminForm">
		<input type="hidden" name="id" value="<?php echo (int) ($item->id ?? 0); ?>">

		<div class="card mb-3">
			<div class="card-body">
				<div class="mb-3">
					<label class="form-label" for="pt-title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label>
					<input class="form-control" id="pt-title" required name="title" value="<?php echo htmlspecialchars((string) ($item->title ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
				</div>

				<div class="mb-3">
					<label class="form-label" for="pt-subject"><?php echo Text::_('COM_PUNGAMAIL_EMAIL_SUBJECT'); ?></label>
					<input class="form-control" id="pt-subject" name="subject" value="<?php echo htmlspecialchars((string) ($item->subject ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
				</div>

				<div>
					<label class="form-label" for="pt-body"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER_BODY_MARKDOWN'); ?></label>
					<textarea class="form-control font-monospace" id="pt-body" name="body_markdown" rows="18"><?php echo htmlspecialchars((string) ($item->body_markdown ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
					<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MARKDOWN_HELP'); ?></div>
					<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER_PLACEHOLDER_HELP'); ?></div>
					<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MARKDOWN_IMAGE_HELP'); ?></div>
					<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_MARKDOWN_TABLE_HELP'); ?></div>
				</div>
			</div>
		</div>

		<div class="card">
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

		<input type="hidden" name="task" value="">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
