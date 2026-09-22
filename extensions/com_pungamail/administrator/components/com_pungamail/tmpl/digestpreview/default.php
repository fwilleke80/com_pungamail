<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Digestpreview\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
use Punga\Component\PungaMail\Administrator\Service\NewsletterRenderer;

$digest = $this->data['digest'];
$rendered = $this->data['rendered'];
$previewHtml = str_replace(
	['href="' . NewsletterRenderer::UNSUBSCRIBE_PLACEHOLDER . '"', 'href="' . NewsletterRenderer::BROWSER_PLACEHOLDER . '"'],
	['aria-disabled="true"', 'aria-disabled="true"'],
	(string) $rendered['html']
);
?>
<div class="container-fluid">
	<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
		<div>
			<h2 class="h5 mb-1"><?php echo htmlspecialchars((string) $rendered['subject'], ENT_QUOTES, 'UTF-8'); ?></h2>
			<div class="small text-muted"><?php echo Text::sprintf('COM_PUNGAMAIL_AUTOMATIC_PREVIEW_SUMMARY', (int) $this->data['item_count'], htmlspecialchars((string) $this->data['cutoff'], ENT_QUOTES, 'UTF-8')); ?></div>
		</div>
		<a class="btn btn-outline-secondary" href="<?php echo Route::_(AdministratorRoute::digest((int) $digest->id)); ?>"><?php echo Text::_('COM_PUNGAMAIL_BACK_TO_EDITOR'); ?></a>
	</div>
	<?php if ((string) $this->data['status'] === 'below_minimum') : ?>
		<div class="alert alert-warning"><?php echo Text::sprintf('COM_PUNGAMAIL_AUTOMATIC_TEST_BELOW_MINIMUM', (int) $this->data['available_count'], (int) $this->data['minimum_items']); ?></div>
	<?php elseif ((string) $this->data['status'] === 'no_content') : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_PUNGAMAIL_AUTOMATIC_TEST_NO_CONTENT'); ?></div>
	<?php endif; ?>
	<div class="card mb-3">
		<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_HTML_PREVIEW'); ?></strong></div>
		<div class="card-body p-0"><iframe sandbox="" title="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_HTML_PREVIEW'), ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:700px;border:0;background:#fff" srcdoc="<?php echo htmlspecialchars($previewHtml, ENT_QUOTES, 'UTF-8'); ?>"></iframe></div>
	</div>
	<div class="card"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_TEXT_PREVIEW'); ?></strong></div><div class="card-body"><pre class="mb-0" style="white-space:pre-wrap"><?php echo htmlspecialchars((string) $rendered['text'], ENT_QUOTES, 'UTF-8'); ?></pre></div></div>
</div>
