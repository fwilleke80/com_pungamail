<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Topics\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;

$document = Factory::getApplication()->getDocument();
$document->getWebAssetManager()->useScript('multiselect');
$order = (string) $this->state->get('list.ordering');
$direction = (string) $this->state->get('list.direction');
$saveOrder = $order === 'a.ordering';
$canChange = Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_pungamail');

if ($saveOrder && $this->items !== [] && $canChange)
{
	$saveOrderingUrl = 'index.php?option=com_pungamail&task=topics.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>
<?php echo \Joomla\CMS\Layout\LayoutHelper::render('pungamail.section_navigation', ['section' => 'audience', 'active' => 'topics'], JPATH_ADMINISTRATOR . '/components/com_pungamail/layouts'); ?>
<form action="<?php echo Route::_(AdministratorRoute::topics()); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<?php if ($this->items === []) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_PUNGAMAIL_NO_TOPICS'); ?></div>
	<?php else : ?>
		<div class="table-responsive">
			<table class="table itemList" id="channelList">
				<thead>
					<tr>
						<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
						<th scope="col" class="w-1 text-center d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $direction, $order, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?></th>
						<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JGLOBAL_TITLE'), 'a.title', $direction, $order); ?></th>
						<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JFIELD_ALIAS_LABEL'), 'a.alias', $direction, $order); ?></th>
						<th scope="col"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIBERS'); ?></th>
						<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JSTATUS'), 'a.state', $direction, $order); ?></th>
						<th scope="col" class="w-3 text-center"><?php echo HTMLHelper::_('searchtools.sort', Text::_('JGRID_HEADING_ID'), 'a.id', $direction, $order); ?></th>
					</tr>
				</thead>
				<tbody<?php if ($saveOrder && $canChange) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($direction); ?>"<?php endif; ?>>
				<?php foreach ($this->items as $i => $item) : ?>
					<tr>
						<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id, false, 'cid', 'cb', (string) $item->title); ?></td>
						<td class="text-center d-none d-md-table-cell">
							<?php
							$iconClass = '';

							if (!$canChange)
							{
								$iconClass = ' inactive';
							}
							elseif (!$saveOrder)
							{
								$iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
							}
							?>
							<span class="sortable-handler<?php echo $iconClass; ?>">
								<span class="icon-ellipsis-v" aria-hidden="true"></span>
							</span>
							<?php if ($canChange && $saveOrder) : ?>
								<input type="text" name="order[]" size="5" value="<?php echo (int) $item->ordering; ?>" class="width-20 text-area-order hidden">
							<?php endif; ?>
						</td>
						<th scope="row">
							<a href="<?php echo Route::_(AdministratorRoute::topic((int) $item->id)); ?>"><?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?></a>
							<?php if ($item->description) : ?><div class="small text-muted"><?php echo htmlspecialchars((string) $item->description, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
						</th>
						<td><code><?php echo htmlspecialchars((string) $item->alias, ENT_QUOTES, 'UTF-8'); ?></code></td>
						<td><?php echo (int) $item->subscriber_count; ?></td>
						<td><?php echo (int) $item->state === 1 ? '<span class="badge bg-success">' . Text::_('JPUBLISHED') . '</span>' : ((int) $item->state === -2 ? '<span class="badge bg-secondary">' . Text::_('JTRASHED') . '</span>' : '<span class="badge bg-warning text-dark">' . Text::_('JUNPUBLISHED') . '</span>'); ?></td>
						<td class="text-center"><?php echo (int) $item->id; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<input type="hidden" name="filter_order" value="<?php echo htmlspecialchars($order, ENT_QUOTES, 'UTF-8'); ?>">
	<input type="hidden" name="filter_order_Dir" value="<?php echo htmlspecialchars($direction, ENT_QUOTES, 'UTF-8'); ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
