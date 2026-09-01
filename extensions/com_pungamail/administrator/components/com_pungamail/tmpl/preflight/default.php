<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Preflight\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

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
	$sender = 'Joomla global mail sender';
}
?>
<div class="container-fluid">
	<div class="alert alert-warning"><strong>Preflight.</strong> This is the exact recipient set as currently stored. Queuing freezes the message and recipient addresses; the queued newsletter becomes immutable.</div>
	<div class="row g-3">
		<div class="col-12 col-xl-5">
			<div class="card mb-3"><div class="card-body">
				<h3 class="h5">Ready to send</h3>
				<dl class="row mb-0">
					<dt class="col-4">Subject</dt><dd class="col-8"><?php echo htmlspecialchars((string) $newsletter->subject, ENT_QUOTES, 'UTF-8'); ?></dd>
					<dt class="col-4">Recipients</dt><dd class="col-8"><?php echo count($recipients); ?> unique addresses</dd>
					<dt class="col-4">Sources</dt><dd class="col-8"><?php foreach ($sourceCounts as $source => $count) : ?><span class="badge bg-secondary me-1"><?php echo htmlspecialchars($source, ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) $count; ?></span><?php endforeach; ?></dd>
					<dt class="col-4">Articles</dt><dd class="col-8"><?php echo count($data['items']); ?></dd>
					<dt class="col-4">Sender</dt><dd class="col-8"><code><?php echo htmlspecialchars($sender, ENT_QUOTES, 'UTF-8'); ?></code></dd>
				</dl>
			</div></div>
			<div class="card mb-3"><div class="card-header"><strong>Recipients</strong></div><div class="card-body" style="max-height:420px;overflow:auto">
				<?php if ($recipients === []) : ?><p class="text-danger">No eligible recipients.</p><?php endif; ?>
				<?php foreach ($recipients as $recipient) : ?>
					<div class="border-bottom py-2"><code><?php echo htmlspecialchars($recipient['email'], ENT_QUOTES, 'UTF-8'); ?></code><div class="small text-muted"><?php echo htmlspecialchars($recipient['source'], ENT_QUOTES, 'UTF-8'); ?></div></div>
				<?php endforeach; ?>
			</div></div>
			<div class="d-flex gap-2">
				<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_pungamail&view=newsletter&id=' . (int) $newsletter->id); ?>">Back to editor</a>
				<?php if ($recipients !== []) : ?>
				<form action="<?php echo Route::_('index.php?option=com_pungamail&task=newsletter.confirmQueue'); ?>" method="post" onsubmit="return confirm('Queue this newsletter for <?php echo count($recipients); ?> recipients? The snapshot will become immutable.');">
					<input type="hidden" name="id" value="<?php echo (int) $newsletter->id; ?>">
					<button class="btn btn-success" type="submit">Queue <?php echo count($recipients); ?> emails</button>
					<?php echo HTMLHelper::_('form.token'); ?>
				</form>
				<?php endif; ?>
			</div>
		</div>
		<div class="col-12 col-xl-7">
			<div class="card mb-3"><div class="card-header"><strong>HTML preview</strong></div><div class="card-body p-0">
				<iframe title="Newsletter HTML preview" style="width:100%;height:620px;border:0;background:#fff" srcdoc="<?php echo htmlspecialchars(str_replace('{{PUNGAMAIL_UNSUBSCRIBE_URL}}', '#', $rendered['html']), ENT_QUOTES, 'UTF-8'); ?>"></iframe>
			</div></div>
			<div class="card"><div class="card-header"><strong>Plain-text preview</strong></div><div class="card-body">
				<pre class="mb-0" style="white-space:pre-wrap"><?php echo htmlspecialchars(str_replace('{{PUNGAMAIL_UNSUBSCRIBE_URL}}', '[personal unsubscribe URL]', $rendered['text']), ENT_QUOTES, 'UTF-8'); ?></pre>
			</div></div>
		</div>
	</div>
</div>
