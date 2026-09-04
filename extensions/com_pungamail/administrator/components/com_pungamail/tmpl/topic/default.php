<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Topic\HtmlView $this */
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\TopicRepository;
$item = $this->item;
$audienceMode = (string) ($item->audience_mode ?? TopicRepository::AUDIENCE_EVERYONE);
?>
<form action="<?php echo Route::_(AdministratorRoute::topics()); ?>" method="post" name="adminForm" id="adminForm">
	<div class="card"><div class="card-body">
		<div class="mb-3"><label class="form-label" for="topic-title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label><input class="form-control" required id="topic-title" name="title" value="<?php echo htmlspecialchars((string) ($item->title ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
		<div class="mb-3"><label class="form-label" for="topic-alias"><?php echo Text::_('JFIELD_ALIAS_LABEL'); ?></label><input class="form-control" id="topic-alias" name="alias" value="<?php echo htmlspecialchars((string) ($item->alias ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_TOPIC_ALIAS_HELP'); ?></div></div>
		<div class="mb-3"><label class="form-label" for="topic-description"><?php echo Text::_('JGLOBAL_DESCRIPTION'); ?></label><textarea class="form-control" id="topic-description" name="description" rows="5"><?php echo htmlspecialchars((string) ($item->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>

		<div class="border-top pt-3 mt-3">
			<label class="form-label" for="topic-audience-mode"><?php echo Text::_('COM_PUNGAMAIL_CHANNEL_WHO_CAN_SUBSCRIBE'); ?></label>
			<select class="form-select" id="topic-audience-mode" name="audience_mode">
				<option value="everyone" <?php echo $audienceMode === 'everyone' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CHANNEL_AUDIENCE_EVERYONE'); ?></option>
				<option value="registered" <?php echo $audienceMode === 'registered' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CHANNEL_AUDIENCE_REGISTERED'); ?></option>
				<option value="groups" <?php echo $audienceMode === 'groups' ? 'selected' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_CHANNEL_AUDIENCE_GROUPS'); ?></option>
			</select>
			<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_CHANNEL_AUDIENCE_HELP'); ?></div>
		</div>
		<div id="topic-group-selection" class="mt-3" <?php echo $audienceMode === 'groups' ? '' : 'hidden'; ?>>
			<div class="form-label"><?php echo Text::_('COM_PUNGAMAIL_CHANNEL_ALLOWED_GROUPS'); ?></div>
			<div class="border rounded p-3" style="max-height:260px;overflow:auto">
			<?php foreach ($this->groups as $group) : ?>
				<div class="form-check"><input class="form-check-input" type="checkbox" name="group_ids[]" value="<?php echo (int) $group->id; ?>" id="topic-group-<?php echo (int) $group->id; ?>" <?php echo in_array((int) $group->id, $this->selectedGroupIds, true) ? 'checked' : ''; ?>><label class="form-check-label" for="topic-group-<?php echo (int) $group->id; ?>"><?php echo htmlspecialchars((string) $group->title, ENT_QUOTES, 'UTF-8'); ?></label></div>
			<?php endforeach; ?>
			</div>
			<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_CHANNEL_ALLOWED_GROUPS_HELP'); ?></div>
		</div>
	</div></div>
	<input type="hidden" name="id" value="<?php echo (int) ($item->id ?? 0); ?>"><input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
	const mode = document.getElementById('topic-audience-mode');
	const groups = document.getElementById('topic-group-selection');
	const update = () => { groups.hidden = mode.value !== 'groups'; };
	mode.addEventListener('change', update);
	update();
});
</script>
