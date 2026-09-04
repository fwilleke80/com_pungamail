<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Newsletters\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRepository;

$listOrder = (string) $this->state->get('list.ordering');
$listDirn = (string) $this->state->get('list.direction');
$statusLabels = [
	NewsletterRepository::STATUS_DRAFT => Text::_('COM_PUNGAMAIL_STATUS_DRAFT'),
	NewsletterRepository::STATUS_QUEUED => Text::_('COM_PUNGAMAIL_STATUS_QUEUED'),
	NewsletterRepository::STATUS_SENDING => Text::_('COM_PUNGAMAIL_STATUS_SENDING'),
	NewsletterRepository::STATUS_SENT => Text::_('COM_PUNGAMAIL_STATUS_SENT'),
	NewsletterRepository::STATUS_SENT_WITH_FAILURES => Text::_('COM_PUNGAMAIL_STATUS_SENT_WITH_FAILURES'),
	NewsletterRepository::STATUS_SCHEDULED => Text::_('COM_PUNGAMAIL_STATUS_SCHEDULED'),
	NewsletterRepository::STATUS_FAILED => Text::_('COM_PUNGAMAIL_STATUS_FAILED'),
	NewsletterRepository::STATUS_CANCELLED => Text::_('COM_PUNGAMAIL_STATUS_CANCELLED'),
];
$siteTimezone=(string)Factory::getApplication()->get('offset','UTC');
?>
<form action="<?php echo Route::_('index.php?option=com_pungamail&view=newsletters'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

	<div class="table-responsive">
		<table class="table itemList" id="newsletterList">
			<thead>
				<tr>
					<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JGLOBAL_TITLE'), 'a.title', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_SUBJECT'), 'a.subject', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JSTATUS'), 'a.state', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_DELIVERY_STATUS'), 'a.status', $listDirn, $listOrder); ?></th>
					<th scope="col" class="text-end"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_RECIPIENTS'), 'a.recipient_count', $listDirn, $listOrder); ?></th>
					<th scope="col" class="text-end"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_SENT'), 'a.sent_count', $listDirn, $listOrder); ?></th>
					<th scope="col" class="text-end"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_FAILED'), 'a.failed_count', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('COM_PUNGAMAIL_SENT_AT'), 'a.sent_at', $listDirn, $listOrder); ?></th>
					<th scope="col" class="w-3 text-center"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JGRID_HEADING_ID'), 'a.id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
					<th scope="row">
						<a href="<?php echo Route::_(\Punga\Component\PungaMail\Administrator\Service\AdministratorRoute::newsletter((int) $item->id)); ?>">
							<?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?>
						</a>
					</th>
					<td><?php echo htmlspecialchars((string) $item->subject, ENT_QUOTES, 'UTF-8'); ?></td>
					<td>
						<?php if ((int) $item->state === -2) : ?>
							<span class="badge bg-secondary"><?php echo Text::_('JTRASHED'); ?></span>
						<?php else : ?>
							<span class="badge bg-success"><?php echo Text::_('COM_PUNGAMAIL_STATE_ACTIVE'); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo htmlspecialchars($statusLabels[(int) $item->status] ?? Text::_('COM_PUNGAMAIL_STATUS_UNKNOWN'), ENT_QUOTES, 'UTF-8'); ?></td>
					<td class="text-end"><?php echo (int) $item->recipient_count; ?></td>
					<td class="text-end"><?php echo (int) $item->sent_count; ?></td>
					<td class="text-end"><?php echo (int) $item->failed_count; ?></td>
					<td><?php if ((int) $item->status === NewsletterRepository::STATUS_SCHEDULED && $item->scheduled_at) : ?><span class="badge bg-info text-dark"><?php echo Text::_('COM_PUNGAMAIL_SCHEDULED_FOR'); ?></span><br><?php echo HTMLHelper::_('date', $item->scheduled_at, Text::_('DATE_FORMAT_LC5'), $siteTimezone); ?><?php else : ?><?php echo $item->sent_at ? HTMLHelper::_('date', $item->sent_at, Text::_('DATE_FORMAT_LC5'), $siteTimezone) : Text::_('COM_PUNGAMAIL_NOT_YET'); ?><?php endif; ?></td>
					<td class="text-center"><?php echo (int) $item->id; ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ($this->items === []) : ?>
				<tr><td colspan="10" class="text-center text-muted py-4"><?php echo Text::_('COM_PUNGAMAIL_NO_NEWSLETTERS'); ?></td></tr>
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
