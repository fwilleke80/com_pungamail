<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Digest\HtmlView $this */
defined('_JEXEC') or die;
use Joomla\CMS\Factory; use Joomla\CMS\HTML\HTMLHelper; use Joomla\CMS\Language\Text; use Joomla\CMS\Router\Route; use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
$item=$this->item;$next=$item?HTMLHelper::_('date',$item->next_run_at,'Y-m-d\TH:i',(string)Factory::getApplication()->get('offset','UTC')):HTMLHelper::_('date','now','Y-m-d\TH:i',(string)Factory::getApplication()->get('offset','UTC'));$confirmAutoChecked=is_object($item)&&property_exists($item,'confirm_auto_send')?(int)$item->confirm_auto_send===1:(string)($item->generation_mode??'draft')==='auto';
?>
<form action="<?php echo Route::_(AdministratorRoute::digests());?>" method="post" name="adminForm" id="adminForm"><div class="row g-3"><div class="col-lg-7">
<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DIGEST_BASICS');?></strong></div><div class="card-body">
<div class="mb-3"><label class="form-label" for="digest-title"><?php echo Text::_('JGLOBAL_TITLE');?></label><input required class="form-control" id="digest-title" name="title" value="<?php echo htmlspecialchars((string)($item->title??''),ENT_QUOTES,'UTF-8');?>"></div>
<div class="mb-3"><label class="form-label" for="digest-template"><?php echo Text::_('COM_PUNGAMAIL_TEMPLATE');?></label><select required class="form-select" id="digest-template" name="template_id"><option value="0"><?php echo Text::_('JSELECT');?></option><?php foreach($this->templates as $template):?><option value="<?php echo (int)$template->id;?>" <?php echo (int)($item->template_id??0)===(int)$template->id?'selected':'';?>><?php echo htmlspecialchars((string)$template->title,ENT_QUOTES,'UTF-8');?></option><?php endforeach;?></select></div>
<div class="mb-3"><label class="form-label" for="digest-subject"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_SUBJECT_PATTERN');?></label><input class="form-control" id="digest-subject" name="subject_pattern" value="<?php echo htmlspecialchars((string)($item->subject_pattern??''),ENT_QUOTES,'UTF-8');?>"><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_SUBJECT_HELP');?></div></div>
</div></div>
<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DIGEST_CONTENT');?></strong></div><div class="card-body"><div class="form-text mb-3"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_ACCESS_HELP');?></div><?php foreach($this->contentTypes as $key=>$type):$selected=in_array((string)$key,$this->sourceKeys,true);$cats=implode(',',$this->categories[(string)$key]??[]);?><div class="border rounded p-2 mb-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="source_keys[]" value="<?php echo htmlspecialchars((string)$key,ENT_QUOTES,'UTF-8');?>" id="digest-source-<?php echo md5((string)$key);?>" <?php echo $selected?'checked':'';?>><label class="form-check-label fw-semibold" for="digest-source-<?php echo md5((string)$key);?>"><?php echo htmlspecialchars((string)$type->label,ENT_QUOTES,'UTF-8');?></label></div><label class="form-label small mt-2" for="digest-categories-<?php echo md5((string)$key);?>"><?php echo Text::_('COM_PUNGAMAIL_CATEGORY_IDS');?></label><input class="form-control form-control-sm" id="digest-categories-<?php echo md5((string)$key);?>" name="source_categories[<?php echo htmlspecialchars((string)$key,ENT_QUOTES,'UTF-8');?>]" value="<?php echo htmlspecialchars($cats,ENT_QUOTES,'UTF-8');?>" placeholder="1, 4, 12"></div><?php endforeach;?></div></div>
</div><div class="col-lg-5">
<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DIGEST_SCHEDULE');?></strong></div><div class="card-body"><div class="mb-3"><label class="form-label" for="digest-next"><?php echo Text::_('COM_PUNGAMAIL_NEXT_RUN');?></label><input required class="form-control" type="datetime-local" id="digest-next" name="next_run_at" value="<?php echo htmlspecialchars((string)$next,ENT_QUOTES,'UTF-8');?>"><div class="form-text"><?php echo Text::sprintf('COM_PUNGAMAIL_SITE_TIMEZONE_HELP',(string)Factory::getApplication()->get('offset','UTC'));?></div></div><div class="mb-3"><label class="form-label" for="digest-repeat"><?php echo Text::_('COM_PUNGAMAIL_RECURRENCE_DAYS');?></label><div class="input-group"><input class="form-control" type="number" min="1" max="365" id="digest-repeat" name="recurrence_days" value="<?php echo max(1,(int)round(((int)($item->recurrence_minutes??10080))/1440));?>"><span class="input-group-text"><?php echo Text::_('COM_PUNGAMAIL_DAYS');?></span></div><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_RECURRENCE_DAYS_HELP');?></div></div><div class="mb-3"><label class="form-label" for="digest-cutoff"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_CUTOFF');?></label><select class="form-select" id="digest-cutoff" name="cutoff_mode"><option value="since_last" <?php echo (string)($item->cutoff_mode??'since_last')==='since_last'?'selected':'';?>><?php echo Text::_('COM_PUNGAMAIL_CUTOFF_SINCE_LAST');?></option><option value="rolling" <?php echo (string)($item->cutoff_mode??'')==='rolling'?'selected':'';?>><?php echo Text::_('COM_PUNGAMAIL_CUTOFF_ROLLING');?></option></select></div><div class="mb-3" id="pm-digest-rolling-period" <?php echo (string)($item->cutoff_mode??'since_last')==='rolling'?'':'hidden';?>><label class="form-label" for="digest-days"><?php echo Text::_('COM_PUNGAMAIL_ROLLING_DAYS');?></label><div class="input-group"><input class="form-control" type="number" min="1" max="365" id="digest-days" name="rolling_days" value="<?php echo max(1,(int)round(((int)($item->rolling_hours??168))/24));?>"><span class="input-group-text"><?php echo Text::_('COM_PUNGAMAIL_DAYS');?></span></div><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_ROLLING_DAYS_HELP');?></div></div><div class="mb-3"><label class="form-label" for="digest-mode"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_MODE');?></label><select class="form-select" id="digest-mode" name="generation_mode"><option value="draft" <?php echo (string)($item->generation_mode??'draft')==='draft'?'selected':'';?>><?php echo Text::_('COM_PUNGAMAIL_DIGEST_CREATE_DRAFT');?></option><option value="auto" <?php echo (string)($item->generation_mode??'')==='auto'?'selected':'';?>><?php echo Text::_('COM_PUNGAMAIL_DIGEST_CREATE_SEND');?></option></select></div><div id="pm-digest-auto-confirm" <?php echo (string)($item->generation_mode??'draft')==='auto'?'':'hidden';?>><div class="form-text text-danger mb-2"><?php echo Text::_('COM_PUNGAMAIL_DIGEST_AUTO_WARNING');?></div><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="confirm_auto_send" value="1" id="confirm-auto" <?php echo $confirmAutoChecked?'checked':'';?>><label class="form-check-label" for="confirm-auto"><?php echo Text::_('COM_PUNGAMAIL_CONFIRM_AUTO_SEND');?></label></div></div><div class="mb-3"><label class="form-label" for="empty-action"><?php echo Text::_('COM_PUNGAMAIL_EMPTY_DIGEST');?></label><select class="form-select" id="empty-action" name="empty_action"><option value="skip" <?php echo (string)($item->empty_action??'skip')==='skip'?'selected':'';?>><?php echo Text::_('COM_PUNGAMAIL_EMPTY_SKIP');?></option><option value="create_draft" <?php echo (string)($item->empty_action??'')==='create_draft'?'selected':'';?>><?php echo Text::_('COM_PUNGAMAIL_EMPTY_DRAFT');?></option></select></div></div></div>
<div class="card mb-3">
	<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_AUDIENCE');?></strong></div>
	<div class="card-body">
		<p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_AUDIENCE_UNION_HELP');?></p>
		<div id="pm-digest-audience-summary" class="alert alert-info small" role="status"
			data-none="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_NONE'),ENT_QUOTES,'UTF-8');?>"
			data-all="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_ALL'),ENT_QUOTES,'UTF-8');?>"
			data-topics="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_TOPICS'),ENT_QUOTES,'UTF-8');?>"
			data-groups="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_AUDIENCE_SUMMARY_GROUPS'),ENT_QUOTES,'UTF-8');?>"></div>
		<div id="pm-digest-audience-warning" class="alert alert-warning small" hidden><?php echo Text::_('COM_PUNGAMAIL_AUDIENCE_ALL_TOPICS_WARNING');?></div>
		<div class="form-check mb-3">
			<input type="hidden" name="include_subscribers" value="0">
			<input class="form-check-input pm-digest-audience-all" type="checkbox" name="include_subscribers" value="1" id="digest-subscribers" <?php echo $item===null||(int)$item->include_subscribers===1?'checked':'';?>>
			<label class="form-check-label fw-semibold" for="digest-subscribers"><?php echo Text::_('COM_PUNGAMAIL_ALL_CONFIRMED_SUBSCRIBERS');?></label>
			<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_ALL_SUBSCRIBERS_HELP');?></div>
		</div>
		<fieldset>
			<legend class="h6 mb-1"><?php echo Text::_('COM_PUNGAMAIL_TOPICS');?></legend>
			<div class="small text-muted mb-2"><?php echo Text::_('COM_PUNGAMAIL_TOPICS_TARGET_HELP');?></div>
			<?php foreach($this->topics as $topic):?>
				<div class="form-check"><input class="form-check-input pm-digest-audience-topic" type="checkbox" name="topic_ids[]" value="<?php echo (int)$topic->id;?>" id="digest-topic-<?php echo (int)$topic->id;?>" <?php echo in_array((int)$topic->id,$this->topicIds,true)?'checked':'';?>><label class="form-check-label" for="digest-topic-<?php echo (int)$topic->id;?>"><?php echo htmlspecialchars((string)$topic->title,ENT_QUOTES,'UTF-8');?></label></div>
			<?php endforeach;?>
		</fieldset>
		<fieldset class="mt-3">
			<legend class="h6 mb-1"><?php echo Text::_('COM_PUNGAMAIL_ADDITIONAL_USER_GROUPS');?></legend>
			<div class="small text-muted mb-2"><?php echo Text::_('COM_PUNGAMAIL_GROUPS_RESPECT_OPT_OUT');?></div>
			<?php foreach($this->groups as $group):?>
				<div class="form-check"><input class="form-check-input pm-digest-audience-group" type="checkbox" name="group_ids[]" value="<?php echo (int)$group->id;?>" id="digest-group-<?php echo (int)$group->id;?>" <?php echo in_array((int)$group->id,$this->groupIds,true)?'checked':'';?>><label class="form-check-label" for="digest-group-<?php echo (int)$group->id;?>"><?php echo htmlspecialchars((string)$group->title,ENT_QUOTES,'UTF-8');?></label></div>
			<?php endforeach;?>
		</fieldset>
	</div>
</div>
</div></div>
<?php if($this->runs!==[]):?><div class="card mt-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DIGEST_HISTORY');?></strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th><?php echo Text::_('JDATE');?></th><th><?php echo Text::_('JSTATUS');?></th><th><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTER');?></th><th><?php echo Text::_('COM_PUNGAMAIL_CONTENT_ITEMS');?></th><th><?php echo Text::_('COM_PUNGAMAIL_DETAILS');?></th></tr></thead><tbody><?php foreach($this->runs as $run):?><tr><td><?php echo HTMLHelper::_('date',$run->started_at,Text::_('DATE_FORMAT_LC5'),'UTC');?></td><td><?php echo htmlspecialchars((string)$run->status,ENT_QUOTES,'UTF-8');?></td><td><?php echo $run->newsletter_id?'<a href="'.Route::_(AdministratorRoute::newsletter((int)$run->newsletter_id)).'">#'.(int)$run->newsletter_id.'</a>':'—';?></td><td><?php echo (int)$run->item_count;?></td><td class="small"><?php echo htmlspecialchars((string)($run->message??''),ENT_QUOTES,'UTF-8');?></td></tr><?php endforeach;?></tbody></table></div></div><?php endif;?>
<input type="hidden" name="id" value="<?php echo (int)($item->id??0);?>"><input type="hidden" name="state" value="<?php echo (int)($item->state??1);?>"><input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token');?></form>
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
