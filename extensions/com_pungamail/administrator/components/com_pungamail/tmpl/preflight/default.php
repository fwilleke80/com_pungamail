<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Preflight\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRenderer;

$data = $this->data;
$newsletter = $data['newsletter'];
$recipients = $data['recipients'];
$rendered = $data['rendered'];
$sourceCounts = $data['source_counts'];
$sender = trim((string) $data['sender']['name']);
if ((string) $data['sender']['email'] !== '')
{
	$sender .= ($sender !== '' ? ' <' : '') . (string) $data['sender']['email'] . ($sender !== '' ? '>' : '');
}
if ($sender === '')
{
	$sender = Text::_('COM_PUNGAMAIL_JOOMLA_GLOBAL_SENDER');
}
$previewHtml = str_replace(
	[
		'href="' . NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER . '"',
		'href="' . NewsletterRenderer::BROWSER_PLACEHOLDER . '"',
	],
	[
		'aria-disabled="true" title="' . htmlspecialchars(Text::_('COM_PUNGAMAIL_PREVIEW_UNSUBSCRIBE_DISABLED'), ENT_QUOTES, 'UTF-8') . '"',
		'aria-disabled="true" title="' . htmlspecialchars(Text::_('COM_PUNGAMAIL_PREVIEW_BROWSER_DISABLED'), ENT_QUOTES, 'UTF-8') . '"',
	],
	(string) $rendered['html']
);
$previewText = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, '[' . Text::_('COM_PUNGAMAIL_PERSONAL_UNSUBSCRIBE_URL') . ']', (string) $rendered['text']);
?>
<div class="container-fluid">
	<div class="alert <?php echo $data['can_send'] ? 'alert-info' : 'alert-danger'; ?>"><strong><?php echo Text::_('COM_PUNGAMAIL_PREFLIGHT'); ?>.</strong> <?php echo Text::_($data['can_send'] ? 'COM_PUNGAMAIL_PREFLIGHT_HELP' : 'COM_PUNGAMAIL_PREFLIGHT_BLOCKED'); ?></div>
	<div class="row g-3">
		<div class="col-12 col-xl-5">
			<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_VALIDATION_CHECKS'); ?></strong></div><div class="list-group list-group-flush"><?php foreach ($data['checks'] as $check) : ?><div class="list-group-item d-flex gap-2"><span class="badge <?php echo $check['level'] === 'error' ? 'bg-danger' : ($check['level'] === 'warning' ? 'bg-warning text-dark' : 'bg-success'); ?>"><?php echo Text::_('COM_PUNGAMAIL_' . strtoupper((string) $check['level'])); ?></span><span><?php echo htmlspecialchars((string) $check['message'], ENT_QUOTES, 'UTF-8'); ?></span></div><?php endforeach; ?></div></div>
			<div class="card mb-3"><div class="card-body">
				<h3 class="h5"><?php echo Text::_('COM_PUNGAMAIL_READY_TO_SEND'); ?></h3>
				<dl class="row mb-0">
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_SUBJECT'); ?></dt><dd class="col-8"><?php echo htmlspecialchars((string) $newsletter->subject, ENT_QUOTES, 'UTF-8'); ?></dd>
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?></dt><dd class="col-8"><?php echo Text::plural('COM_PUNGAMAIL_UNIQUE_ADDRESSES', count($recipients)); ?></dd>
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_SOURCES'); ?></dt><dd class="col-8"><?php foreach ($sourceCounts as $source => $count) : ?><span class="badge bg-secondary me-1"><?php echo htmlspecialchars($source, ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) $count; ?></span><?php endforeach; ?></dd>
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_CONTENT_ITEMS'); ?></dt><dd class="col-8"><?php echo count($data['items']); ?></dd>
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_SENDER'); ?></dt><dd class="col-8"><code><?php echo htmlspecialchars($sender, ENT_QUOTES, 'UTF-8'); ?></code></dd>
				</dl>
			</div></div>
			<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?></strong></div><div class="card-body" style="max-height:420px;overflow:auto">
				<?php if ($recipients === []) : ?><p class="text-danger"><?php echo Text::_('COM_PUNGAMAIL_NO_ELIGIBLE_RECIPIENTS'); ?></p><?php endif; ?>
				<?php foreach ($recipients as $recipient) : ?>
					<div class="border-bottom py-2">
						<div class="fw-semibold"><?php echo htmlspecialchars((string) $recipient['recipient_name'], ENT_QUOTES, 'UTF-8'); ?></div>
						<code><?php echo htmlspecialchars((string) $recipient['email'], ENT_QUOTES, 'UTF-8'); ?></code>
						<div class="small text-muted"><?php echo htmlspecialchars((string) $recipient['source'], ENT_QUOTES, 'UTF-8'); ?></div>
					</div>
				<?php endforeach; ?>
			</div></div>
			<?php if ($data['excluded'] !== []) : ?><div class="card mb-3"><div class="card-header"><strong><?php echo Text::sprintf('COM_PUNGAMAIL_EXCLUDED_RECIPIENTS', count($data['excluded'])); ?></strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?php echo Text::_('COM_PUNGAMAIL_EMAIL'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SOURCE'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_REASON'); ?></th></tr></thead><tbody><?php foreach ($data['excluded'] as $excluded) : ?><tr><td><code><?php echo htmlspecialchars((string) $excluded['email'], ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars((string) $excluded['source'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo Text::_('COM_PUNGAMAIL_EXCLUSION_' . strtoupper((string) $excluded['reason'])); ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>
			<div class="d-flex gap-2">
				<a class="btn btn-outline-secondary" href="<?php echo Route::_(\Punga\Component\PungaMail\Administrator\Service\AdministratorRoute::newsletter((int) $newsletter->id)); ?>"><?php echo Text::_('COM_PUNGAMAIL_BACK_TO_EDITOR'); ?></a>
				<?php if ($recipients !== [] && $data['can_send']) : ?>
				<form action="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.confirmQueue'); ?>" method="post" onsubmit="return confirm('<?php echo htmlspecialchars(Text::sprintf('COM_PUNGAMAIL_CONFIRM_QUEUE', count($recipients)), ENT_QUOTES, 'UTF-8'); ?>');">
					<input type="hidden" name="id" value="<?php echo (int) $newsletter->id; ?>">
					<button class="btn btn-success" type="submit"><?php echo Text::plural('COM_PUNGAMAIL_QUEUE_EMAILS', count($recipients)); ?></button>
					<?php echo HTMLHelper::_('form.token'); ?>
				</form>
				<form action="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.schedule'); ?>" method="post"><input type="hidden" name="id" value="<?php echo (int) $newsletter->id; ?>"><div class="input-group"><input required class="form-control" type="datetime-local" name="scheduled_at" aria-label="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SCHEDULE_AT'), ENT_QUOTES, 'UTF-8'); ?>"><button class="btn btn-outline-success" type="submit"><?php echo Text::_('COM_PUNGAMAIL_SCHEDULE_SEND'); ?></button></div><div class="form-text"><?php echo Text::sprintf('COM_PUNGAMAIL_SITE_TIMEZONE_HELP', (string) Joomla\CMS\Factory::getApplication()->get('offset', 'UTC')); ?></div><?php echo HTMLHelper::_('form.token'); ?></form>
				<?php endif; ?>
			</div>
		</div>
		<div class="col-12 col-xl-7">
			<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_HTML_PREVIEW'); ?></strong></div><div class="card-body p-0">
				<iframe sandbox="" title="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_HTML_PREVIEW'), ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:620px;border:0;background:#fff" srcdoc="<?php echo htmlspecialchars($previewHtml, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
			</div></div>
			<div class="card"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_TEXT_PREVIEW'); ?></strong></div><div class="card-body">
				<pre class="mb-0" style="white-space:pre-wrap"><?php echo htmlspecialchars($previewText, ENT_QUOTES, 'UTF-8'); ?></pre>
			</div></div>
		</div>
	</div>
</div>
