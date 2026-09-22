<?php
/** @package Punga.Mail @subpackage Administrator.Layout */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$item = $displayData['item'] ?? null;
$scope = (string) ($item->campaign_scope ?? 'inherit');
$value = static fn (string $name): string => htmlspecialchars((string) ($item->{$name} ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="card mb-3">
	<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_CAMPAIGN_TRACKING'); ?></strong></div>
	<div class="card-body">
		<p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_CAMPAIGN_TRACKING_OVERRIDE_HELP'); ?></p>
		<div class="mb-3">
			<label class="form-label" for="campaign-scope"><?php echo Text::_('COM_PUNGAMAIL_CAMPAIGN_SCOPE'); ?></label>
			<select class="form-select" id="campaign-scope" name="campaign_scope">
				<option value="inherit" <?php echo $scope === 'inherit' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_INHERIT'); ?></option>
				<option value="disabled" <?php echo $scope === 'disabled' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CAMPAIGN_SCOPE_DISABLED'); ?></option>
				<option value="internal" <?php echo $scope === 'internal' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CAMPAIGN_SCOPE_INTERNAL'); ?></option>
				<option value="all" <?php echo $scope === 'all' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CAMPAIGN_SCOPE_ALL'); ?></option>
			</select>
		</div>
		<div class="row g-3">
			<div class="col-md-6"><label class="form-label" for="utm-source">utm_source</label><input class="form-control" id="utm-source" name="utm_source" value="<?php echo $value('utm_source'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_INHERIT'), ENT_QUOTES, 'UTF-8'); ?>"></div>
			<div class="col-md-6"><label class="form-label" for="utm-medium">utm_medium</label><input class="form-control" id="utm-medium" name="utm_medium" value="<?php echo $value('utm_medium'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_INHERIT'), ENT_QUOTES, 'UTF-8'); ?>"></div>
			<div class="col-md-6"><label class="form-label" for="utm-campaign">utm_campaign</label><input class="form-control" id="utm-campaign" name="utm_campaign" value="<?php echo $value('utm_campaign'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_INHERIT'), ENT_QUOTES, 'UTF-8'); ?>"></div>
			<div class="col-md-6"><label class="form-label" for="utm-id">utm_id</label><input class="form-control" id="utm-id" name="utm_id" value="<?php echo $value('utm_id'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_INHERIT'), ENT_QUOTES, 'UTF-8'); ?>"></div>
			<div class="col-12"><label class="form-label" for="utm-content">utm_content</label><input class="form-control" id="utm-content" name="utm_content" value="<?php echo $value('utm_content'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_INHERIT'), ENT_QUOTES, 'UTF-8'); ?>"></div>
		</div>
		<div class="form-text mt-2"><?php echo Text::_('COM_PUNGAMAIL_CAMPAIGN_PLACEHOLDER_HELP'); ?></div>
	</div>
</div>
