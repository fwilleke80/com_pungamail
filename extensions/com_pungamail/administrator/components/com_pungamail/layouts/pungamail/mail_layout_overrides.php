<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Layout
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Punga\Component\PungaMail\Administrator\Helper\MediaFieldHelper;

$style = is_array($displayData['style'] ?? null) ? $displayData['style'] : [];
$item = $displayData['item'] ?? null;
$prefix = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($displayData['prefix'] ?? 'pm-layout')) ?: 'pm-layout';
$helpKey = (string) ($displayData['help_key'] ?? 'COM_PUNGAMAIL_LAYOUT_INHERIT_HELP');
$customCss = (string) ($item->custom_css ?? '');
$headingMode = (string) ($item->heading_mode ?? 'inherit');
$browserView = (int) ($item->browser_view ?? -1);
$logoMode = (string) ($style['logo_mode'] ?? (array_key_exists('logo_url', $style) ? 'custom' : 'inherit'));
$headerBackgroundImageMode = (string) ($style['header_background_image_mode'] ?? (array_key_exists('header_background_image', $style) ? 'custom' : 'inherit'));
$footerBackgroundImageMode = (string) ($style['footer_background_image_mode'] ?? (array_key_exists('footer_background_image', $style) ? 'custom' : 'inherit'));
$footerReasonMode = (string) ($style['footer_reason_mode'] ?? 'inherit');

/** @param string $key @param string $label @param string $placeholder @return void */
$renderText = static function (string $key, string $label, string $placeholder = '') use ($style, $prefix): void
{
	$value = (string) ($style[$key] ?? '');
	?>
	<div class="col-md-6">
		<label class="form-label" for="<?php echo $prefix . '-' . $key; ?>"><?php echo Text::_($label); ?></label>
		<input class="form-control" id="<?php echo $prefix . '-' . $key; ?>" name="style[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($placeholder !== '' ? Text::_($placeholder) : Text::_('COM_PUNGAMAIL_INHERIT'), ENT_QUOTES, 'UTF-8'); ?>">
	</div>
	<?php
};

/** @param string $key @param string $label @param array<string,string> $options @return void */
$renderSelect = static function (string $key, string $label, array $options) use ($style, $prefix): void
{
	$value = (string) ($style[$key] ?? '');
	?>
	<div class="col-md-6">
		<label class="form-label" for="<?php echo $prefix . '-' . $key; ?>"><?php echo Text::_($label); ?></label>
		<select class="form-select" id="<?php echo $prefix . '-' . $key; ?>" name="style[<?php echo $key; ?>]">
			<option value=""><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
			<?php foreach ($options as $optionValue => $optionLabel) : ?>
				<option value="<?php echo htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $value === $optionValue ? 'selected' : ''; ?>><?php echo Text::_($optionLabel); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
};
?>
<p class="small text-muted mb-3"><?php echo Text::_($helpKey); ?></p>

<div class="card mb-3">
	<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_PAGE'); ?></strong></div>
	<div class="card-body"><div class="row g-3">
		<?php $renderText('content_width', 'COM_PUNGAMAIL_STYLE_CONTENT_WIDTH'); ?>
		<?php $renderText('outer_background', 'COM_PUNGAMAIL_STYLE_OUTER_BACKGROUND'); ?>
	</div></div>
</div>

<div class="card mb-3">
	<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_BROWSER_BAR'); ?></strong></div>
	<div class="card-body"><div class="row g-3">
		<div class="col-md-6">
			<label class="form-label" for="<?php echo $prefix; ?>-browser-view"><?php echo Text::_('COM_PUNGAMAIL_BROWSER_VIEW'); ?></label>
			<select class="form-select" id="<?php echo $prefix; ?>-browser-view" name="browser_view">
				<option value="-1" <?php echo $browserView === -1 ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
				<option value="1" <?php echo $browserView === 1 ? 'selected' : ''; ?>><?php echo Text::_('JENABLED'); ?></option>
				<option value="0" <?php echo $browserView === 0 ? 'selected' : ''; ?>><?php echo Text::_('JDISABLED'); ?></option>
			</select>
		</div>
		<?php $renderText('browser_background', 'COM_PUNGAMAIL_LAYOUT_BACKGROUND'); ?>
		<?php $renderText('browser_link_color', 'COM_PUNGAMAIL_LAYOUT_LINK_COLOR'); ?>
		<?php $renderSelect('browser_alignment', 'COM_PUNGAMAIL_LAYOUT_ALIGNMENT', ['left' => 'COM_PUNGAMAIL_LAYOUT_LEFT', 'center' => 'COM_PUNGAMAIL_LAYOUT_CENTER', 'right' => 'COM_PUNGAMAIL_LAYOUT_RIGHT']); ?>
		<?php $renderText('browser_padding', 'COM_PUNGAMAIL_LAYOUT_PADDING'); ?>
	</div></div>
</div>

<div class="card mb-3">
	<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_HEADER'); ?></strong></div>
	<div class="card-body"><div class="row g-3">
		<div class="col-md-6">
			<label class="form-label" for="<?php echo $prefix; ?>-heading-mode"><?php echo Text::_('COM_PUNGAMAIL_MAIL_BODY_HEADING'); ?></label>
			<select class="form-select" id="<?php echo $prefix; ?>-heading-mode" name="heading_mode">
				<option value="inherit" <?php echo $headingMode === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
				<option value="custom" <?php echo $headingMode === 'custom' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CUSTOM'); ?></option>
				<option value="site" <?php echo $headingMode === 'site' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_USE_SITE_NAME'); ?></option>
				<option value="none" <?php echo $headingMode === 'none' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_NO_HEADING'); ?></option>
			</select>
		</div>
		<div class="col-md-6">
			<label class="form-label" for="<?php echo $prefix; ?>-mail-heading"><?php echo Text::_('COM_PUNGAMAIL_CUSTOM_HEADING'); ?></label>
			<input class="form-control" id="<?php echo $prefix; ?>-mail-heading" name="mail_heading" value="<?php echo htmlspecialchars((string) ($item->mail_heading ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
		</div>
		<?php $renderText('heading_background', 'COM_PUNGAMAIL_STYLE_HEADING_BACKGROUND', 'COM_PUNGAMAIL_LAYOUT_COLOR_INHERIT_HINT'); ?>
		<div class="col-md-6">
			<label class="form-label" for="<?php echo $prefix; ?>-header-background-image-mode"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_BACKGROUND_IMAGE'); ?></label>
			<select class="form-select" id="<?php echo $prefix; ?>-header-background-image-mode" name="style[header_background_image_mode]">
				<option value="inherit" <?php echo $headerBackgroundImageMode === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
				<option value="custom" <?php echo $headerBackgroundImageMode === 'custom' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_USE_IMAGE'); ?></option>
				<option value="none" <?php echo $headerBackgroundImageMode === 'none' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_NO_IMAGE'); ?></option>
			</select>
		</div>
		<div class="col-12">
			<label class="form-label" for="<?php echo $prefix; ?>-header-background-image"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_SELECTED_IMAGE'); ?></label>
			<?php echo MediaFieldHelper::imageInput('com_pungamail.' . $prefix . '.header-background', 'style', 'header_background_image', (string) ($style['header_background_image'] ?? ''), $prefix . '-header-background-image'); ?>
			<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_BACKGROUND_IMAGE_OVERRIDE_HELP'); ?></div>
		</div>
		<?php $renderText('mail_heading_color', 'COM_PUNGAMAIL_STYLE_MAIL_HEADING_COLOR'); ?>
		<?php $renderSelect('header_alignment', 'COM_PUNGAMAIL_LAYOUT_ALIGNMENT', ['left' => 'COM_PUNGAMAIL_LAYOUT_LEFT', 'center' => 'COM_PUNGAMAIL_LAYOUT_CENTER', 'right' => 'COM_PUNGAMAIL_LAYOUT_RIGHT']); ?>
		<?php $renderText('header_padding', 'COM_PUNGAMAIL_LAYOUT_PADDING'); ?>
		<?php $renderText('header_gap', 'COM_PUNGAMAIL_LAYOUT_HEADER_GAP'); ?>
		<div class="col-md-6">
			<label class="form-label" for="<?php echo $prefix; ?>-logo-mode"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_LOGO'); ?></label>
			<select class="form-select" id="<?php echo $prefix; ?>-logo-mode" name="style[logo_mode]">
				<option value="inherit" <?php echo $logoMode === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
				<option value="custom" <?php echo $logoMode === 'custom' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_USE_LOGO'); ?></option>
				<option value="none" <?php echo $logoMode === 'none' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_NO_LOGO'); ?></option>
			</select>
		</div>
		<div class="col-12">
			<label class="form-label" for="<?php echo $prefix; ?>-logo-url"><?php echo Text::_('COM_PUNGAMAIL_STYLE_LOGO_URL'); ?></label>
			<?php echo MediaFieldHelper::imageInput('com_pungamail.' . $prefix . '.logo', 'style', 'logo_url', (string) ($style['logo_url'] ?? ''), $prefix . '-logo-url'); ?>
			<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_LOGO_OVERRIDE_HELP'); ?></div>
		</div>
		<?php $renderText('logo_width', 'COM_PUNGAMAIL_STYLE_LOGO_WIDTH'); ?>
		<?php $renderSelect('logo_position', 'COM_PUNGAMAIL_LAYOUT_LOGO_POSITION', ['above' => 'COM_PUNGAMAIL_LAYOUT_LOGO_ABOVE', 'below' => 'COM_PUNGAMAIL_LAYOUT_LOGO_BELOW']); ?>
	</div></div>
</div>

<div class="card mb-3">
	<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_CONTENT'); ?></strong></div>
	<div class="card-body"><div class="row g-3">
		<?php $renderText('content_background', 'COM_PUNGAMAIL_STYLE_CONTENT_BACKGROUND'); ?>
		<?php $renderText('text_color', 'COM_PUNGAMAIL_STYLE_TEXT_COLOR'); ?>
		<?php $renderText('heading_color', 'COM_PUNGAMAIL_STYLE_HEADING_COLOR'); ?>
		<?php $renderText('link_color', 'COM_PUNGAMAIL_STYLE_LINK_COLOR'); ?>
		<?php $renderText('font_family', 'COM_PUNGAMAIL_STYLE_FONT_FAMILY'); ?>
		<?php $renderText('font_size', 'COM_PUNGAMAIL_STYLE_FONT_SIZE'); ?>
		<?php $renderText('content_padding', 'COM_PUNGAMAIL_STYLE_CONTENT_PADDING'); ?>
	</div></div>
</div>

<div class="card mb-3">
	<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_FOOTER'); ?></strong></div>
	<div class="card-body"><div class="row g-3">
		<?php $renderText('footer_background', 'COM_PUNGAMAIL_LAYOUT_BACKGROUND'); ?>
		<div class="col-md-6">
			<label class="form-label" for="<?php echo $prefix; ?>-footer-background-image-mode"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_BACKGROUND_IMAGE'); ?></label>
			<select class="form-select" id="<?php echo $prefix; ?>-footer-background-image-mode" name="style[footer_background_image_mode]">
				<option value="inherit" <?php echo $footerBackgroundImageMode === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
				<option value="custom" <?php echo $footerBackgroundImageMode === 'custom' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_USE_IMAGE'); ?></option>
				<option value="none" <?php echo $footerBackgroundImageMode === 'none' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_NO_IMAGE'); ?></option>
			</select>
		</div>
		<div class="col-12">
			<label class="form-label" for="<?php echo $prefix; ?>-footer-background-image"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_SELECTED_IMAGE'); ?></label>
			<?php echo MediaFieldHelper::imageInput('com_pungamail.' . $prefix . '.footer-background', 'style', 'footer_background_image', (string) ($style['footer_background_image'] ?? ''), $prefix . '-footer-background-image'); ?>
			<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_BACKGROUND_IMAGE_OVERRIDE_HELP'); ?></div>
		</div>
		<?php $renderText('footer_color', 'COM_PUNGAMAIL_STYLE_FOOTER_COLOR'); ?>
		<?php $renderText('footer_link_color', 'COM_PUNGAMAIL_LAYOUT_LINK_COLOR'); ?>
		<?php $renderSelect('footer_alignment', 'COM_PUNGAMAIL_LAYOUT_ALIGNMENT', ['left' => 'COM_PUNGAMAIL_LAYOUT_LEFT', 'center' => 'COM_PUNGAMAIL_LAYOUT_CENTER', 'right' => 'COM_PUNGAMAIL_LAYOUT_RIGHT']); ?>
		<?php $renderText('footer_padding', 'COM_PUNGAMAIL_LAYOUT_PADDING'); ?>
		<?php $renderSelect('footer_divider', 'COM_PUNGAMAIL_LAYOUT_FOOTER_DIVIDER', ['1' => 'JSHOW', '0' => 'JHIDE']); ?>
		<?php $renderText('footer_divider_color', 'COM_PUNGAMAIL_LAYOUT_DIVIDER_COLOR'); ?>
		<div class="col-md-6">
			<label class="form-label" for="<?php echo $prefix; ?>-footer-reason-mode"><?php echo Text::_('COM_PUNGAMAIL_CONFIG_FOOTER_REASON'); ?></label>
			<select class="form-select" id="<?php echo $prefix; ?>-footer-reason-mode" name="style[footer_reason_mode]">
				<option value="inherit" <?php echo $footerReasonMode === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
				<option value="custom" <?php echo $footerReasonMode === 'custom' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CUSTOM'); ?></option>
				<option value="none" <?php echo $footerReasonMode === 'none' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_NO_FOOTER_REASON'); ?></option>
			</select>
		</div>
		<div class="col-12">
			<label class="form-label" for="<?php echo $prefix; ?>-footer-reason"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_FOOTER_TEXT'); ?></label>
			<textarea class="form-control" id="<?php echo $prefix; ?>-footer-reason" name="style[footer_reason]" rows="4"><?php echo htmlspecialchars((string) ($style['footer_reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
			<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_FOOTER_TEXT_HELP'); ?></div>
		</div>
	</div></div>
</div>

<div class="card mb-3">
	<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_ADVANCED'); ?></strong></div>
	<div class="card-body">
		<label class="form-label" for="<?php echo $prefix; ?>-custom-css"><?php echo Text::_('COM_PUNGAMAIL_STYLE_CUSTOM_CSS'); ?></label>
		<textarea class="form-control font-monospace" id="<?php echo $prefix; ?>-custom-css" name="custom_css" rows="8"><?php echo htmlspecialchars($customCss, ENT_QUOTES, 'UTF-8'); ?></textarea>
		<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_LAYOUT_CUSTOM_CSS_HELP'); ?></div>
	</div>
</div>
