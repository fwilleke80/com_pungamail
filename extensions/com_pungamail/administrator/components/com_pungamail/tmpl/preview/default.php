<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Preview\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRenderer;

$newsletter = $this->data['newsletter'];
$rendered = $this->data['rendered'];
$previewHtml = str_replace('href="' . NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER . '"', 'aria-disabled="true" title="' . htmlspecialchars(Text::_('COM_PUNGAMAIL_PREVIEW_UNSUBSCRIBE_DISABLED'), ENT_QUOTES, 'UTF-8') . '"', (string) $rendered['html']);
$previewText = str_replace(NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER, '[' . Text::_('COM_PUNGAMAIL_PERSONAL_UNSUBSCRIBE_URL') . ']', (string) $rendered['text']);
?>
<div class="container-fluid">
	<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
		<div>
			<h2 class="h5 mb-1"><?php echo htmlspecialchars((string) $newsletter->subject, ENT_QUOTES, 'UTF-8'); ?></h2>
			<div class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_PREVIEW_EXACT_RENDERER_HELP'); ?></div>
		</div>
		<a class="btn btn-outline-secondary" href="<?php echo Route::_(\Punga\Component\PungaMail\Administrator\Service\AdministratorRoute::newsletter((int) $newsletter->id)); ?>"><?php echo Text::_('COM_PUNGAMAIL_BACK_TO_EDITOR'); ?></a>
	</div>

	<div class="card mb-3">
		<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_HTML_PREVIEW'); ?></strong></div>
		<div class="card-body p-0">
			<iframe sandbox="" title="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_HTML_PREVIEW'), ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:700px;border:0;background:#fff" srcdoc="<?php echo htmlspecialchars($previewHtml, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
		</div>
	</div>
	<div class="card">
		<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_TEXT_PREVIEW'); ?></strong></div>
		<div class="card-body"><pre class="mb-0" style="white-space:pre-wrap"><?php echo htmlspecialchars($previewText, ENT_QUOTES, 'UTF-8'); ?></pre></div>
	</div>
</div>
