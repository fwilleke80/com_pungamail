<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Import\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$preview = $this->preview;
?>
<div class="row g-3">
	<div class="col-12 col-xl-8">
		<div class="card mb-3">
			<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_IMPORT_SUBSCRIBERS'); ?></strong></div>
			<div class="card-body">
				<p class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_IMPORT_CONSENT_WARNING'); ?></p>
				<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post" enctype="multipart/form-data"><label class="form-label" for="csv-file"><?php echo Text::_('COM_PUNGAMAIL_CSV_FILE'); ?></label><div class="input-group"><input required class="form-control" id="csv-file" name="csv_file" type="file" accept=".csv,text/csv"><button class="btn btn-primary" name="task" value="import.preview" type="submit"><?php echo Text::_('COM_PUNGAMAIL_PREVIEW_IMPORT'); ?></button></div><?php echo HTMLHelper::_('form.token'); ?></form>
			</div>
		</div>

		<?php if ($preview !== null) : ?>
		<div class="card">
			<div class="card-header"><strong><?php echo Text::sprintf('COM_PUNGAMAIL_IMPORT_PREVIEW_COUNT', (int) $preview['total']); ?></strong></div>
			<div class="card-body">
				<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post">
					<div class="row g-3 mb-3">
					<?php foreach (['email', 'name', 'status', 'topics'] as $field) : ?>
						<div class="col-md-6"><label class="form-label" for="map-<?php echo $field; ?>"><?php echo Text::_('COM_PUNGAMAIL_CSV_' . strtoupper($field)); ?></label><select class="form-select" id="map-<?php echo $field; ?>" name="mapping[<?php echo $field; ?>]"><option value=""><?php echo Text::_('COM_PUNGAMAIL_NOT_MAPPED'); ?></option><?php foreach ($preview['headers'] as $header) : ?><option value="<?php echo htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8'); ?>" <?php echo mb_strtolower((string) $header, 'UTF-8') === $field ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
					<?php endforeach; ?>
					</div>
					<div class="form-check border border-danger rounded p-3 ps-5 mb-3"><input class="form-check-input" id="reactivate" name="reactivate" type="checkbox" value="1"><label class="form-check-label fw-semibold" for="reactivate"><?php echo Text::_('COM_PUNGAMAIL_IMPORT_REACTIVATE'); ?></label><div class="form-text text-danger"><?php echo Text::_('COM_PUNGAMAIL_IMPORT_REACTIVATE_WARNING'); ?></div></div>
					<div class="d-flex gap-2"><button class="btn btn-success" name="task" value="import.commit" type="submit"><?php echo Text::_('COM_PUNGAMAIL_COMMIT_IMPORT'); ?></button><button class="btn btn-outline-secondary" name="task" value="import.clear" type="submit"><?php echo Text::_('JCANCEL'); ?></button></div>
					<?php echo HTMLHelper::_('form.token'); ?>
				</form>
			</div>
			<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><?php foreach ($preview['headers'] as $header) : ?><th><?php echo htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8'); ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($preview['rows'] as $row) : ?><tr><?php foreach ($preview['headers'] as $index => $header) : ?><td><?php echo htmlspecialchars((string) ($row[$index] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
		</div>
		<?php endif; ?>
	</div>
	<div class="col-12 col-xl-4">
		<div class="card"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_EXPORT_SUBSCRIBERS'); ?></strong></div><div class="card-body"><form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post"><label class="form-label" for="export-scope"><?php echo Text::_('COM_PUNGAMAIL_EXPORT_SCOPE'); ?></label><select class="form-select mb-3" id="export-scope" name="scope"><option value="all"><?php echo Text::_('COM_PUNGAMAIL_EXPORT_ALL'); ?></option><option value="active"><?php echo Text::_('COM_PUNGAMAIL_EXPORT_ACTIVE'); ?></option><option value="unsubscribed"><?php echo Text::_('COM_PUNGAMAIL_EXPORT_UNSUBSCRIBED'); ?></option><option value="suppressed"><?php echo Text::_('COM_PUNGAMAIL_EXPORT_SUPPRESSED'); ?></option></select><div class="fw-semibold mb-2"><?php echo Text::_('COM_PUNGAMAIL_TOPICS_OPTIONAL'); ?></div><?php foreach ($this->topics as $topic) : ?><div class="form-check"><input class="form-check-input" type="checkbox" name="topic_ids[]" value="<?php echo (int) $topic->id; ?>" id="export-topic-<?php echo (int) $topic->id; ?>"><label class="form-check-label" for="export-topic-<?php echo (int) $topic->id; ?>"><?php echo htmlspecialchars((string) $topic->title, ENT_QUOTES, 'UTF-8'); ?></label></div><?php endforeach; ?><button class="btn btn-primary mt-3" name="task" value="import.export" type="submit"><?php echo Text::_('COM_PUNGAMAIL_DOWNLOAD_CSV'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form></div></div>
	</div>
</div>
