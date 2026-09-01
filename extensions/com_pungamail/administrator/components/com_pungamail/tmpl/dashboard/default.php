<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Dashboard\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$data = $this->data;
$subscribers = $data['subscribers'];
$queue = $data['queue'];
$newsletters = $data['newsletters'];
$task = $data['task'];
?>
<div class="container-fluid">
	<div class="row g-3 mb-3">
		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100"><div class="card-body">
				<div class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_VERSION'); ?></div>
				<div class="display-6">Punga Mail <?php echo htmlspecialchars((string) $data['version'], ENT_QUOTES, 'UTF-8'); ?></div>
			</div></div>
		</div>
		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100"><div class="card-body">
				<div class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBERS'); ?></div>
				<div class="display-6"><?php echo (int) $subscribers['subscribed']; ?></div>
				<div class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_SUBSCRIBER_DETAIL', (int) $subscribers['pending'], (int) $subscribers['suppressed']); ?></div>
			</div></div>
		</div>
		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100"><div class="card-body">
				<div class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTERS'); ?></div>
				<div class="display-6"><?php echo (int) $newsletters['active']; ?></div>
				<div class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_NEWSLETTER_DETAIL', (int) $newsletters['drafts'], (int) $newsletters['sent'], (int) $newsletters['trashed']); ?></div>
			</div></div>
		</div>
		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100"><div class="card-body">
				<div class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_MAIL_QUEUE'); ?></div>
				<div class="display-6"><?php echo (int) $queue['pending']; ?></div>
				<div class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_DASHBOARD_QUEUE_DETAIL', (int) $queue['processing'], (int) $queue['failed']); ?></div>
			</div></div>
		</div>
	</div>

	<div class="row g-3">
		<div class="col-12 col-xl-7">
			<div class="card h-100">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_QUICK_ACTIONS'); ?></strong></div>
				<div class="card-body d-flex flex-wrap gap-2 align-items-start">
					<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletter'); ?>"><?php echo Text::_('COM_PUNGAMAIL_NEW_NEWSLETTER'); ?></a>
					<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletters'); ?>"><?php echo Text::_('COM_PUNGAMAIL_NEWSLETTERS'); ?></a>
					<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=subscribers'); ?>"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBERS'); ?></a>
				</div>
			</div>
		</div>
		<div class="col-12 col-xl-5">
			<div class="card h-100">
				<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_SCHEDULED_DELIVERY'); ?></strong></div>
				<div class="card-body">
				<?php if ($task === null) : ?>
					<div class="alert alert-warning mb-0"><?php echo Text::_('COM_PUNGAMAIL_TASK_NOT_CONFIGURED'); ?></div>
				<?php else : ?>
					<dl class="row mb-0">
						<dt class="col-5"><?php echo Text::_('JSTATUS'); ?></dt>
						<dd class="col-7"><?php echo (int) $task->state === 1 ? Text::_('JENABLED') : Text::_('JDISABLED'); ?></dd>
						<dt class="col-5"><?php echo Text::_('COM_PUNGAMAIL_LAST_RUN'); ?></dt>
						<dd class="col-7"><?php echo $task->last_execution ? HTMLHelper::_('date', $task->last_execution, Text::_('DATE_FORMAT_LC5'), 'UTC') : Text::_('COM_PUNGAMAIL_NEVER'); ?></dd>
						<dt class="col-5"><?php echo Text::_('COM_PUNGAMAIL_NEXT_RUN'); ?></dt>
						<dd class="col-7"><?php echo $task->next_execution ? HTMLHelper::_('date', $task->next_execution, Text::_('DATE_FORMAT_LC5'), 'UTC') : '—'; ?></dd>
					</dl>
				<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
