<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Subscribers\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\SubscriberRepository;

$listOrder = (string) $this->state->get('list.ordering');
$listDirn = (string) $this->state->get('list.direction');
$statusLabels = [
	SubscriberRepository::STATUS_PENDING => Text::_('COM_PUNGAMAIL_SUBSCRIBER_PENDING'),
	SubscriberRepository::STATUS_SUBSCRIBED => Text::_('COM_PUNGAMAIL_SUBSCRIBER_SUBSCRIBED'),
	SubscriberRepository::STATUS_UNSUBSCRIBED => Text::_('COM_PUNGAMAIL_SUBSCRIBER_UNSUBSCRIBED'),
];
?>
<?php echo \Joomla\CMS\Layout\LayoutHelper::render('pungamail.section_navigation', ['section' => 'audience', 'active' => 'subscribers'], JPATH_ADMINISTRATOR . '/components/com_pungamail/layouts'); ?>
<form action="<?php echo Route::_(AdministratorRoute::subscribers()); ?>" method="post" name="adminForm" id="adminForm">
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
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_BOUNCES'), 's.bounce_count', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JDATE'), 's.created', $listDirn, $listOrder); ?></th>
					<th scope="col" class="w-3 text-center"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JGRID_HEADING_ID'), 's.id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
					<th scope="row">
						<?php if (Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_pungamail')) : ?>
							<a href="<?php echo Route::_(AdministratorRoute::subscriber((int) $item->id)); ?>"><code><?php echo htmlspecialchars((string) $item->email, ENT_QUOTES, 'UTF-8'); ?></code></a>
						<?php else : ?>
							<code><?php echo htmlspecialchars((string) $item->email, ENT_QUOTES, 'UTF-8'); ?></code>
						<?php endif; ?>
						<?php if ($item->user_name) : ?>
							<div class="small text-muted"><?php echo htmlspecialchars((string) $item->user_name, ENT_QUOTES, 'UTF-8'); ?> · <?php echo Text::sprintf('COM_PUNGAMAIL_JOOMLA_USER_ID', (int) $item->user_id); ?></div>
						<?php endif; ?>
					</th>
					<td><?php echo htmlspecialchars((string) $item->source, ENT_QUOTES, 'UTF-8'); ?></td>
					<td>
						<?php if ((int) $item->status === SubscriberRepository::STATUS_UNSUBSCRIBED) : ?>
							<span class="badge bg-secondary"><?php echo Text::_('COM_PUNGAMAIL_STATUS_UNSUBSCRIBED'); ?></span>
						<?php elseif ((int) $item->status === SubscriberRepository::STATUS_SUBSCRIBED) : ?>
							<span class="badge bg-success"><?php echo Text::_('COM_PUNGAMAIL_STATUS_ACTIVE'); ?></span>
						<?php else : ?>
							<span class="badge bg-warning text-dark"><?php echo htmlspecialchars($statusLabels[(int) $item->status] ?? Text::_('COM_PUNGAMAIL_STATUS_UNKNOWN'), ENT_QUOTES, 'UTF-8'); ?></span>
						<?php endif; ?>
						<?php if ($item->suppression_reason && (int) $item->status !== SubscriberRepository::STATUS_UNSUBSCRIBED) : ?>
							<div class="mt-1"><span class="badge bg-danger"><?php echo Text::_(in_array((string) $item->suppression_reason, ['hard-bounce', 'soft-bounce-threshold'], true) ? 'COM_PUNGAMAIL_STATUS_BOUNCED' : 'COM_PUNGAMAIL_STATUS_SUPPRESSED'); ?></span></div>
							<div class="small text-muted"><?php echo htmlspecialchars((string) $item->suppression_reason, ENT_QUOTES, 'UTF-8'); ?></div>
						<?php endif; ?>
					</td>
					<td><?php echo $item->confirmed_at ? HTMLHelper::_('date', $item->confirmed_at, Text::_('DATE_FORMAT_LC5'), 'UTC') : '—'; ?></td>
					<td><strong><?php echo (int) $item->bounce_count; ?></strong><?php if ($item->last_bounce_at) : ?><div class="small"><?php echo htmlspecialchars((string) $item->last_bounce_class, ENT_QUOTES, 'UTF-8'); ?> · <?php echo HTMLHelper::_('date', $item->last_bounce_at, Text::_('DATE_FORMAT_LC5'), 'UTC'); ?></div><div class="small text-muted"><?php echo htmlspecialchars((string) $item->last_bounce_reason, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><?php if (in_array((string) $item->suppression_reason, ['hard-bounce', 'soft-bounce-threshold'], true)) : ?><button class="btn btn-sm btn-outline-warning mt-1" type="submit" name="id" value="<?php echo (int) $item->id; ?>" formaction="<?php echo Route::_('index.php?option=com_pungamail&task=subscriber.clearBounceSuppression'); ?>" formmethod="post" onclick="return confirm('<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_CLEAR_BOUNCE_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>');"><?php echo Text::_('COM_PUNGAMAIL_CLEAR_BOUNCE_SUPPRESSION'); ?></button><?php endif; ?></td>
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
