<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Contentlayouts\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\ContentLayoutRepository;

$legacyCount = (int) ($this->legacyOverrides['newsletters'] ?? 0) + (int) ($this->legacyOverrides['templates'] ?? 0);
?>
<div class="container-fluid">
	<div class="card mb-3">
		<div class="card-body">
			<p class="mb-1"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUTS_DESC'); ?></p>
			<div class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUTS_TABLE_PLACEHOLDERS_DESC'); ?></div>
		</div>
	</div>

	<?php if ($legacyCount > 0) : ?>
		<div class="alert alert-warning">
			<strong><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUTS_LEGACY_TITLE'); ?></strong>
			<div><?php echo Text::sprintf('COM_PUNGAMAIL_CONTENT_LAYOUTS_LEGACY_DESC', (int) ($this->legacyOverrides['newsletters'] ?? 0), (int) ($this->legacyOverrides['templates'] ?? 0)); ?></div>
		</div>
	<?php endif; ?>

	<div class="table-responsive">
		<table class="table itemList align-middle">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_PUNGAMAIL_CONTENT_TYPE'); ?></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_CONTENT_SOURCE_ALIAS'); ?></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_CONTENT_SOURCE_TABLE'); ?></th>
					<th><?php echo Text::_('COM_PUNGAMAIL_LAYOUT'); ?></th>
					<th><?php echo Text::_('JGLOBAL_FIELD_MODIFIED_LABEL'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($this->items as $item) : ?>
				<tr>
					<th>
						<a href="<?php echo Route::_(AdministratorRoute::contentLayout((string) $item->source_key)); ?>">
							<?php echo htmlspecialchars((string) ($item->source_key === ContentLayoutRepository::DEFAULT_KEY ? Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_DEFAULT') : $item->label), ENT_QUOTES, 'UTF-8'); ?>
						</a>
					</th>
					<td><code><?php echo htmlspecialchars((string) $item->source_key, ENT_QUOTES, 'UTF-8'); ?></code></td>
					<td><?php echo $item->table !== '' ? '<code>' . htmlspecialchars((string) $item->table, ENT_QUOTES, 'UTF-8') . '</code>' : '—'; ?></td>
					<td>
						<?php if ($item->source_key === ContentLayoutRepository::DEFAULT_KEY) : ?>
							<span class="badge text-bg-primary"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_DEFAULT_BADGE'); ?></span>
						<?php elseif ($item->custom) : ?>
							<span class="badge text-bg-info"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_CUSTOM'); ?></span>
						<?php else : ?>
							<span class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_USES_DEFAULT'); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo $item->modified !== '' ? HTMLHelper::_('date', $item->modified, Text::_('DATE_FORMAT_LC5'), 'UTC') : '—'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
