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
$sender = trim((string) $data['from_name']);
if ((string) $data['from_email'] !== '')
{
	$sender .= ($sender !== '' ? ' <' : '') . (string) $data['from_email'] . ($sender !== '' ? '>' : '');
}
if ($sender === '')
{
	$sender = Text::_('COM_PUNGAMAIL_JOOMLA_GLOBAL_SENDER');
}
$previewHtml = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, '#', (string) $rendered['html']);
$previewText = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, '[' . Text::_('COM_PUNGAMAIL_PERSONAL_UNSUBSCRIBE_URL') . ']', (string) $rendered['text']);
?>
<div class="container-fluid">
	<div class="alert alert-warning"><strong><?php echo Text::_('COM_PUNGAMAIL_PREFLIGHT'); ?>.</strong> <?php echo Text::_('COM_PUNGAMAIL_PREFLIGHT_HELP'); ?></div>
	<div class="row g-3">
		<div class="col-12 col-xl-5">
			<div class="card mb-3"><div class="card-body">
				<h3 class="h5"><?php echo Text::_('COM_PUNGAMAIL_READY_TO_SEND'); ?></h3>
				<dl class="row mb-0">
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_SUBJECT'); ?></dt><dd class="col-8"><?php echo htmlspecialchars((string) $newsletter->subject, ENT_QUOTES, 'UTF-8'); ?></dd>
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?></dt><dd class="col-8"><?php echo Text::plural('COM_PUNGAMAIL_UNIQUE_ADDRESSES', count($recipients)); ?></dd>
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_SOURCES'); ?></dt><dd class="col-8"><?php foreach ($sourceCounts as $source => $count) : ?><span class="badge bg-secondary me-1"><?php echo htmlspecialchars($source, ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) $count; ?></span><?php endforeach; ?></dd>
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_ARTICLES'); ?></dt><dd class="col-8"><?php echo count($data['items']); ?></dd>
					<dt class="col-4"><?php echo Text::_('COM_PUNGAMAIL_SENDER'); ?></dt><dd class="col-8"><code><?php echo htmlspecialchars($sender, ENT_QUOTES, 'UTF-8'); ?></code></dd>
				</dl>
			</div></div>
			<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_RECIPIENTS'); ?></strong></div><div class="card-body" style="max-height:420px;overflow:auto">
				<?php if ($recipients === []) : ?><p class="text-danger"><?php echo Text::_('COM_PUNGAMAIL_NO_ELIGIBLE_RECIPIENTS'); ?></p><?php endif; ?>
				<?php foreach ($recipients as $recipient) : ?>
					<div class="border-bottom py-2"><code><?php echo htmlspecialchars($recipient['email'], ENT_QUOTES, 'UTF-8'); ?></code><div class="small text-muted"><?php echo htmlspecialchars($recipient['source'], ENT_QUOTES, 'UTF-8'); ?></div></div>
				<?php endforeach; ?>
			</div></div>
			<div class="d-flex gap-2">
				<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletter&id=' . (int) $newsletter->id); ?>"><?php echo Text::_('COM_PUNGAMAIL_BACK_TO_EDITOR'); ?></a>
				<?php if ($recipients !== []) : ?>
				<form action="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.confirmQueue'); ?>" method="post" onsubmit="return confirm('<?php echo htmlspecialchars(Text::sprintf('COM_PUNGAMAIL_CONFIRM_QUEUE', count($recipients)), ENT_QUOTES, 'UTF-8'); ?>');">
					<input type="hidden" name="id" value="<?php echo (int) $newsletter->id; ?>">
					<button class="btn btn-success" type="submit"><?php echo Text::plural('COM_PUNGAMAIL_QUEUE_EMAILS', count($recipients)); ?></button>
					<?php echo HTMLHelper::_('form.token'); ?>
				</form>
				<?php endif; ?>
			</div>
		</div>
		<div class="col-12 col-xl-7">
			<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_HTML_PREVIEW'); ?></strong></div><div class="card-body p-0">
				<iframe title="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_HTML_PREVIEW'), ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:620px;border:0;background:#fff" srcdoc="<?php echo htmlspecialchars($previewHtml, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
			</div></div>
			<div class="card"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_TEXT_PREVIEW'); ?></strong></div><div class="card-body">
				<pre class="mb-0" style="white-space:pre-wrap"><?php echo htmlspecialchars($previewText, ENT_QUOTES, 'UTF-8'); ?></pre>
			</div></div>
		</div>
	</div>
</div>
