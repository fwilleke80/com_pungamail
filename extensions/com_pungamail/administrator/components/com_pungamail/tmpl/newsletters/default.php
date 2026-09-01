<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Newsletters\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRepository;

$statusLabels = [
	NewsletterRepository::STATUS_DRAFT => 'Draft',
	NewsletterRepository::STATUS_QUEUED => 'Queued',
	NewsletterRepository::STATUS_SENDING => 'Sending',
	NewsletterRepository::STATUS_SENT => 'Sent',
	NewsletterRepository::STATUS_SENT_WITH_FAILURES => 'Sent with failures',
];
?>
<div class="container-fluid">
	<div class="d-flex gap-2 mb-3">
		<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletter'); ?>">New newsletter</a>
		<form action="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.processQueue'); ?>" method="post">
			<button class="btn btn-outline-secondary" type="submit">Process queue now</button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	</div>

	<div class="card">
		<div class="card-body p-0">
			<table class="table table-striped mb-0">
				<thead>
					<tr>
						<th>Newsletter</th>
						<th>Status</th>
						<th>Recipients</th>
						<th>Sent</th>
						<th>Failed</th>
						<th>Sent at</th>
						<th class="text-end">Actions</th>
					</tr>
				</thead>
				<tbody>
				<?php if ($this->items === []) : ?>
					<tr><td colspan="7" class="text-center text-muted py-4">No newsletters yet.</td></tr>
				<?php endif; ?>
				<?php foreach ($this->items as $item) : ?>
					<tr>
						<td>
							<a href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletter&id=' . (int) $item->id); ?>">
								<?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?>
							</a>
							<div class="small text-muted"><?php echo htmlspecialchars((string) $item->subject, ENT_QUOTES, 'UTF-8'); ?></div>
						</td>
						<td><?php echo htmlspecialchars($statusLabels[(int) $item->status] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
						<td><?php echo (int) $item->recipient_count; ?></td>
						<td><?php echo (int) $item->sent_count; ?></td>
						<td><?php echo (int) $item->failed_count; ?></td>
						<td><?php echo $item->sent_at ? htmlspecialchars((string) $item->sent_at, ENT_QUOTES, 'UTF-8') . ' UTC' : '—'; ?></td>
						<td class="text-end">
							<form class="d-inline" action="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.duplicate'); ?>" method="post">
								<input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
								<button class="btn btn-sm btn-outline-secondary" type="submit">Duplicate</button>
								<?php echo HTMLHelper::_('form.token'); ?>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
