<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Subscribers\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\SubscriberRepository;

$listOrder = (string) $this->state->get('list.ordering');
$listDirn = (string) $this->state->get('list.direction');
$statusLabels = [
	SubscriberRepository::STATUS_PENDING => Text::_('COM_PUNGAMAIL_SUBSCRIBER_PENDING'),
	SubscriberRepository::STATUS_SUBSCRIBED => Text::_('COM_PUNGAMAIL_SUBSCRIBER_SUBSCRIBED'),
	SubscriberRepository::STATUS_UNSUBSCRIBED => Text::_('COM_PUNGAMAIL_SUBSCRIBER_UNSUBSCRIBED'),
];
?>
<form action="<?php echo Route::_('index.php?option=com_pungamail&view=subscribers'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<p class="small text-muted mb-3"><?php echo Text::_('COM_PUNGAMAIL_SUPPRESSION_HELP'); ?></p>

	<div class="table-responsive">
		<table class="table itemList" id="subscriberList">
			<thead>
				<tr>
					<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_EMAIL'), 's.email', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_SOURCE'), 's.source', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_SUBSCRIPTION_STATUS'), 's.status', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_CONFIRMED'), 's.confirmed_at', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_SUPPRESSED'), 'x.reason', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JDATE'), 's.created', $listDirn, $listOrder); ?></th>
					<th scope="col" class="w-3 text-center"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JGRID_HEADING_ID'), 's.id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
					<th scope="row">
						<code><?php echo htmlspecialchars((string) $item->email, ENT_QUOTES, 'UTF-8'); ?></code>
						<?php if ($item->user_name) : ?>
							<div class="small text-muted"><?php echo htmlspecialchars((string) $item->user_name, ENT_QUOTES, 'UTF-8'); ?> · <?php echo Text::sprintf('COM_PUNGAMAIL_JOOMLA_USER_ID', (int) $item->user_id); ?></div>
						<?php endif; ?>
					</th>
					<td><?php echo htmlspecialchars((string) $item->source, ENT_QUOTES, 'UTF-8'); ?></td>
					<td><?php echo htmlspecialchars($statusLabels[(int) $item->status] ?? Text::_('COM_PUNGAMAIL_STATUS_UNKNOWN'), ENT_QUOTES, 'UTF-8'); ?></td>
					<td><?php echo $item->confirmed_at ? HTMLHelper::_('date', $item->confirmed_at, Text::_('DATE_FORMAT_LC5'), 'UTC') : '—'; ?></td>
					<td>
						<?php if ($item->suppression_reason) : ?>
							<span class="badge bg-danger"><?php echo Text::_('JYES'); ?></span>
							<div class="small text-muted"><?php echo htmlspecialchars((string) $item->suppression_reason, ENT_QUOTES, 'UTF-8'); ?></div>
						<?php else : ?>
							<span class="badge bg-success"><?php echo Text::_('JNO'); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5'), 'UTC'); ?></td>
					<td class="text-center"><?php echo (int) $item->id; ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ($this->items === []) : ?>
				<tr><td colspan="8" class="text-center text-muted py-4"><?php echo Text::_('COM_PUNGAMAIL_NO_SUBSCRIBERS'); ?></td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<input type="hidden" name="filter_order" value="<?php echo htmlspecialchars($listOrder, ENT_QUOTES, 'UTF-8'); ?>">
	<input type="hidden" name="filter_order_Dir" value="<?php echo htmlspecialchars($listDirn, ENT_QUOTES, 'UTF-8'); ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
