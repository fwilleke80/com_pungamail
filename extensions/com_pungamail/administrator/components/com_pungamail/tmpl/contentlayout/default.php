<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Contentlayout\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Helper\MarkdownEditorHelper;
use Punga\Component\PungaMail\Administrator\Service\ContentLayoutRepository;

$item = $this->item;
$isDefault = (string) $item->source_key === ContentLayoutRepository::DEFAULT_KEY;
$genericPlaceholders = [
	'{title}' => 'COM_PUNGAMAIL_CONTENT_PLACEHOLDER_TITLE',
	'{title_link}' => 'COM_PUNGAMAIL_CONTENT_PLACEHOLDER_TITLE_LINK',
	'{publish_date}' => 'COM_PUNGAMAIL_CONTENT_PLACEHOLDER_PUBLISH_DATE',
	'{excerpt}' => 'COM_PUNGAMAIL_CONTENT_PLACEHOLDER_EXCERPT',
	'{read_more}' => 'COM_PUNGAMAIL_CONTENT_PLACEHOLDER_READ_MORE',
	'{url}' => 'COM_PUNGAMAIL_CONTENT_PLACEHOLDER_URL',
	'{content_type}' => 'COM_PUNGAMAIL_CONTENT_PLACEHOLDER_CONTENT_TYPE',
];
$editorPlaceholders = array_keys($genericPlaceholders);
foreach ((array) $item->columns as $column)
{
	$name = (string) ($column['name'] ?? '');
	if ($name !== '' && !isset($genericPlaceholders['{' . $name . '}']))
	{
		$editorPlaceholders[] = '{' . $name . '}';
	}
}
?>
<style>
.pm-placeholder-table code { white-space: nowrap; }
.pm-layout-sidebar { position: sticky; top: 1rem; }
</style>
<div class="container-fluid">
	<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post" name="adminForm" id="adminForm" data-pm-unsaved-warning="1">
		<input type="hidden" name="source_key" value="<?php echo htmlspecialchars((string) $item->source_key, ENT_QUOTES, 'UTF-8'); ?>">
		<div class="row g-4 align-items-start">
			<div class="col-12 col-xl-8">
				<div class="card mb-3">
					<div class="card-body">
						<h2 class="h5 mb-1"><?php echo htmlspecialchars((string) $item->label, ENT_QUOTES, 'UTF-8'); ?></h2>
						<?php if (!$isDefault) : ?>
							<div class="small text-muted mb-3"><code><?php echo htmlspecialchars((string) $item->source_key, ENT_QUOTES, 'UTF-8'); ?></code> · <code><?php echo htmlspecialchars((string) $item->table, ENT_QUOTES, 'UTF-8'); ?></code></div>
							<div class="form-check form-switch mb-3">
								<input class="form-check-input" type="checkbox" role="switch" id="pm-use-custom-layout" name="use_custom" value="1" <?php echo $item->custom ? 'checked' : ''; ?>>
								<label class="form-check-label" for="pm-use-custom-layout"><strong><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_USE_CUSTOM'); ?></strong></label>
							</div>
							<div class="form-text mb-3"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_USE_CUSTOM_DESC'); ?></div>
						<?php else : ?>
							<input type="hidden" name="use_custom" value="1">
							<p class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_DEFAULT_DESC'); ?></p>
						<?php endif; ?>
						<div id="pm-layout-editor-wrap">
							<label class="form-label" for="pm-content-layout-markdown"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_MARKDOWN'); ?></label>
							<?php echo MarkdownEditorHelper::render(
								'layout_markdown',
								'pm-content-layout-markdown',
								(string) $item->layout_markdown,
								14,
								$editorPlaceholders,
								[Text::_('COM_PUNGAMAIL_CONTENT_LAYOUT_MARKDOWN_DESC'), Text::_('COM_PUNGAMAIL_MARKDOWN_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_IMAGE_HELP'), Text::_('COM_PUNGAMAIL_MARKDOWN_TABLE_HELP')]
							); ?>
						</div>
					</div>
				</div>
			</div>

			<aside class="col-12 col-xl-4">
				<div class="card pm-layout-sidebar">
					<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_AVAILABLE_PLACEHOLDERS'); ?></strong></div>
					<div class="card-body">
						<p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_AVAILABLE_PLACEHOLDERS_DESC'); ?></p>
						<h3 class="h6"><?php echo Text::_('COM_PUNGAMAIL_PUNGAMAIL_PLACEHOLDERS'); ?></h3>
						<div class="table-responsive mb-3">
							<table class="table table-sm pm-placeholder-table">
								<tbody>
								<?php foreach ($genericPlaceholders as $placeholder => $descriptionKey) : ?>
									<tr><td><code><?php echo htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'); ?></code></td><td class="small"><?php echo Text::_($descriptionKey); ?></td></tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>

						<?php if (!$isDefault) : ?>
							<h3 class="h6"><?php echo Text::_('COM_PUNGAMAIL_DATABASE_PLACEHOLDERS'); ?></h3>
							<p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_DATABASE_PLACEHOLDERS_DESC'); ?></p>
							<?php if ((array) $item->columns === []) : ?>
								<div class="text-muted small"><?php echo Text::_('COM_PUNGAMAIL_NO_DATABASE_PLACEHOLDERS'); ?></div>
							<?php else : ?>
								<div class="table-responsive" style="max-height:34rem;overflow:auto">
									<table class="table table-sm pm-placeholder-table">
										<thead><tr><th><?php echo Text::_('COM_PUNGAMAIL_PLACEHOLDER'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_DATABASE_TYPE'); ?></th></tr></thead>
										<tbody>
										<?php foreach ((array) $item->columns as $column) : ?>
											<?php $name = (string) ($column['name'] ?? ''); if ($name === '' || isset($genericPlaceholders['{' . $name . '}'])) { continue; } ?>
											<tr>
												<td>
													<code>{<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>}</code>
													<?php if ((bool) ($column['date_like'] ?? false)) : ?>
														<div class="small text-muted mt-1"><code>{<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>|date}</code><br><code>{<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>|time}</code><br><code>{<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>|datetime}</code></div>
													<?php endif; ?>
												</td>
												<td class="small text-muted"><?php echo htmlspecialchars((string) ($column['type'] ?: '—'), ENT_QUOTES, 'UTF-8'); ?></td>
											</tr>
										<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			</aside>
		</div>
		<input type="hidden" name="task" value="">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
<?php if (!$isDefault) : ?>
<script>
document.addEventListener('DOMContentLoaded', function ()
{
	const toggle = document.getElementById('pm-use-custom-layout');
	const wrap = document.getElementById('pm-layout-editor-wrap');

	if (!toggle || !wrap)
	{
		return;
	}

	const update = function ()
	{
		wrap.classList.toggle('opacity-50', !toggle.checked);
		wrap.style.pointerEvents = toggle.checked ? '' : 'none';
	};

	toggle.addEventListener('change', update);
	update();
});
</script>
<?php endif; ?>
