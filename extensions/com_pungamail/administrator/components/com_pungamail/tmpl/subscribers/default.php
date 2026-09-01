<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Subscribers\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\SubscriberRepository;

$statusLabels = [
	SubscriberRepository::STATUS_PENDING => 'Pending',
	SubscriberRepository::STATUS_SUBSCRIBED => 'Subscribed',
	SubscriberRepository::STATUS_UNSUBSCRIBED => 'Unsubscribed',
];
?>
<div class="container-fluid">
	<div class="card"><div class="card-body p-0">
		<table class="table table-striped mb-0">
			<thead><tr><th>Email</th><th>Type/source</th><th>Status</th><th>Confirmed</th><th>Suppression</th><th class="text-end">Actions</th></tr></thead>
			<tbody>
			<?php if ($this->items === []) : ?><tr><td colspan="6" class="text-center text-muted py-4">No subscribers yet.</td></tr><?php endif; ?>
			<?php foreach ($this->items as $item) : ?>
				<tr>
					<td><code><?php echo htmlspecialchars((string) $item->email, ENT_QUOTES, 'UTF-8'); ?></code><?php if ($item->user_name) : ?><div class="small text-muted"><?php echo htmlspecialchars((string) $item->user_name, ENT_QUOTES, 'UTF-8'); ?> · Joomla user #<?php echo (int) $item->user_id; ?></div><?php endif; ?></td>
					<td><?php echo htmlspecialchars((string) $item->source, ENT_QUOTES, 'UTF-8'); ?></td>
					<td><?php echo htmlspecialchars($statusLabels[(int) $item->status] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
					<td><?php echo $item->confirmed_at ? htmlspecialchars((string) $item->confirmed_at, ENT_QUOTES, 'UTF-8') . ' UTC' : '—'; ?></td>
					<td><?php echo $item->suppression_reason ? htmlspecialchars((string) $item->suppression_reason, ENT_QUOTES, 'UTF-8') : '—'; ?></td>
					<td class="text-end">
						<?php if ((int) $item->status === SubscriberRepository::STATUS_SUBSCRIBED) : ?>
						<form class="d-inline" action="<?php echo Route::_('index.php?option=com_pungamail&task=subscriber.unsubscribe'); ?>" method="post">
							<input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
							<button class="btn btn-sm btn-outline-danger" type="submit">Unsubscribe</button>
							<?php echo HTMLHelper::_('form.token'); ?>
						</form>
						<?php else : ?>
						<form class="d-inline" action="<?php echo Route::_('index.php?option=com_pungamail&task=subscriber.requestConfirmation'); ?>" method="post">
							<input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
							<button class="btn btn-sm btn-outline-secondary" type="submit">Send confirmation</button>
							<?php echo HTMLHelper::_('form.token'); ?>
						</form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div></div>
</div>
