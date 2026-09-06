<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Subscriber\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;

$softBounceThreshold = max(1, (int) ComponentHelper::getParams('com_pungamail')->get('soft_bounce_threshold', 3));
?>
<?php echo \Joomla\CMS\Layout\LayoutHelper::render('pungamail.section_navigation', ['section' => 'audience', 'active' => 'subscribers'], JPATH_ADMINISTRATOR . '/components/com_pungamail/layouts'); ?>
<form action="<?php echo Route::_(AdministratorRoute::subscriber((int) ($this->item->id ?? 0))); ?>" method="post" name="adminForm" id="adminForm">
	<p class="text-muted"><?php echo Text::_($this->item === null ? 'COM_PUNGAMAIL_ADD_SUBSCRIBER_HELP' : 'COM_PUNGAMAIL_EDIT_SUBSCRIBER_HELP'); ?></p>
	<div class="row g-3">
		<div class="col-12 col-lg-6">
			<div class="card h-100">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBER_IDENTITY_PERMISSION'); ?></strong></div>
				<div class="card-body">
			<?php echo $this->form?->renderField('id') ?? ''; ?>
			<?php if ($this->item === null) : ?>
				<?php echo $this->form?->renderField('recipient_type') ?? ''; ?>
				<?php echo $this->form?->renderField('email') ?? ''; ?>
				<?php echo $this->form?->renderField('user_id') ?? ''; ?>
			<?php else : ?>
				<div class="mb-3">
					<div class="form-label"><?php echo Text::_('COM_PUNGAMAIL_EMAIL'); ?></div>
					<code><?php echo htmlspecialchars((string) $this->item->email, ENT_QUOTES, 'UTF-8'); ?></code>
					<?php if ($this->item->user_id !== null) : ?><div class="form-text"><?php echo Text::sprintf('COM_PUNGAMAIL_JOOMLA_USER_ID', (int) $this->item->user_id); ?></div><?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ($this->item !== null && $this->item->user_id !== null) : ?>
				<div class="mb-3">
					<div class="form-label"><?php echo Text::_('COM_PUNGAMAIL_JOOMLA_DISPLAY_NAME'); ?></div>
					<div class="form-control-plaintext fw-semibold"><?php echo htmlspecialchars((string) ($this->item->user_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
					<div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_JOOMLA_DISPLAY_NAME_DESC'); ?></div>
				</div>
			<?php else : ?>
				<?php echo $this->form?->renderField('recipient_name') ?? ''; ?>
			<?php endif; ?>
			<?php if ($this->item !== null) : ?>
				<?php echo $this->form?->renderField('status') ?? ''; ?>
			<?php endif; ?>
					<p class="form-text"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBER_PERMISSION_HELP'); ?></p>
				</div>
			</div>
		</div>

		<div class="col-12 col-lg-6">
			<div class="card h-100">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_TOPICS'); ?></strong></div>
				<div class="card-body">
				<?php if ($this->topics !== []) : ?>
				<fieldset>
					<legend class="h5"><?php echo Text::_('COM_PUNGAMAIL_TOPICS'); ?></legend>
					<p class="form-text"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBER_TOPICS_HELP'); ?></p>
					<?php foreach ($this->topics as $topic) : ?>
						<div class="form-check mb-2">
							<input class="form-check-input pm-subscriber-topic" type="checkbox" name="jform[topic_ids][]" value="<?php echo (int) $topic->id; ?>" id="subscriber-topic-<?php echo (int) $topic->id; ?>" <?php echo in_array((int) $topic->id, $this->selectedTopicIds, true) ? 'checked' : ''; ?> <?php echo !($topic->eligible ?? true) ? 'disabled' : ''; ?>>
							<label class="form-check-label" for="subscriber-topic-<?php echo (int) $topic->id; ?>">
								<?php echo htmlspecialchars((string) $topic->title, ENT_QUOTES, 'UTF-8'); ?>
								<?php if ((int) $topic->state !== 1) : ?><span class="badge bg-secondary ms-1"><?php echo Text::_('JUNPUBLISHED'); ?></span><?php endif; ?>
							</label>
							<?php if (trim((string) $topic->description) !== '') : ?><div class="form-text"><?php echo htmlspecialchars((string) $topic->description, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
							<?php if (!($topic->eligible ?? true)) : ?>
								<div class="form-text text-warning">
								<?php if ((string) ($topic->audience_mode ?? '') === 'groups') : ?>
									<?php echo Text::sprintf('COM_PUNGAMAIL_CHANNEL_REQUIRES_GROUPS', htmlspecialchars(implode(', ', (array) ($topic->audience_group_titles ?? [])), ENT_QUOTES, 'UTF-8')); ?>
								<?php else : ?>
									<?php echo Text::_('COM_PUNGAMAIL_CHANNEL_REQUIRES_REGISTERED'); ?>
								<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</fieldset>
					<div id="pm-subscriber-no-topics" class="alert alert-info small mt-3 mb-0" <?php echo $this->selectedTopicIds !== [] ? 'hidden' : ''; ?>><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBER_TOPICS_NONE_HELP'); ?></div>
				<?php else : ?>
					<div class="alert alert-secondary small mb-0"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBER_NO_CHANNELS'); ?></div>
				<?php endif; ?>
				</div>
			</div>
		</div>

		<?php if ($this->item !== null && ($this->suppressionReason !== null || (int) ($this->item->bounce_count ?? 0) > 0)) : ?>
		<div class="col-12">
			<div class="card">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBER_DELIVERY_PROBLEMS'); ?></strong></div>
				<div class="card-body">
					<?php if ($this->suppressionReason !== null) : ?>
						<?php $blockMessageKey = match ((string) $this->suppressionReason)
						{
							'hard-bounce' => 'COM_PUNGAMAIL_SUBSCRIBER_DELIVERY_STOPPED_PERMANENT',
							'soft-bounce-threshold' => 'COM_PUNGAMAIL_SUBSCRIBER_DELIVERY_STOPPED_REPEATED',
							default => 'COM_PUNGAMAIL_SUBSCRIBER_DELIVERY_STOPPED',
						}; ?>
						<div class="alert alert-danger"><?php echo Text::_($blockMessageKey); ?></div>
						<?php if (in_array((string) $this->suppressionReason, ['hard-bounce', 'soft-bounce-threshold'], true)) : ?>
							<button class="btn btn-outline-warning btn-sm mb-3" type="submit" name="id" value="<?php echo (int) $this->item->id; ?>" formaction="<?php echo Route::_('index.php?option=com_pungamail&task=subscriber.clearBounceSuppression'); ?>" formmethod="post" onclick="return confirm('<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_CLEAR_BOUNCE_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>');"><?php echo Text::_('COM_PUNGAMAIL_CLEAR_BOUNCE_SUPPRESSION'); ?></button>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ((int) ($this->item->bounce_count ?? 0) > 0) : ?>
						<dl class="row small mb-0">
							<dt class="col-sm-3"><?php echo Text::_('COM_PUNGAMAIL_BOUNCES'); ?></dt>
							<dd class="col-sm-9"><?php echo (int) $this->item->bounce_count; ?></dd>
							<?php if (!empty($this->item->last_bounce_at)) : ?>
								<?php $bounceClassKey = (string) ($this->item->last_bounce_class ?? '') === 'hard' ? 'COM_PUNGAMAIL_BOUNCE_CLASS_PERMANENT' : 'COM_PUNGAMAIL_BOUNCE_CLASS_TEMPORARY'; ?>
								<dt class="col-sm-3"><?php echo Text::_('COM_PUNGAMAIL_LAST_BOUNCE'); ?></dt>
								<dd class="col-sm-9">
									<?php echo HTMLHelper::_('date', $this->item->last_bounce_at, Text::_('DATE_FORMAT_LC5'), 'UTC'); ?> · <?php echo Text::_($bounceClassKey); ?>
									<?php if ((string) ($this->item->last_bounce_class ?? '') === 'soft' && $this->suppressionReason === null) : ?>
										<div class="text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_TEMPORARY_FAILURE_PROGRESS', (int) ($this->item->soft_bounce_count ?? 0), $softBounceThreshold); ?></div>
									<?php endif; ?>
								</dd>
							<?php endif; ?>
						</dl>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>
document.addEventListener('DOMContentLoaded', function ()
{
	const checks = Array.from(document.querySelectorAll('.pm-subscriber-topic'));
	const note = document.getElementById('pm-subscriber-no-topics');
	const update = function ()
	{
		if (note)
		{
			note.hidden = checks.some(function (check)
			{
				return check.checked;
			});
		}
	};

	checks.forEach(function (check)
	{
		check.addEventListener('change', update);
	});
	update();
});
</script>
