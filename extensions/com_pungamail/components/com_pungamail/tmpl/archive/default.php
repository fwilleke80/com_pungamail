<?php
/** @var \Punga\Component\PungaMail\Site\View\Archive\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Punga\Component\PungaMail\Site\Service\ArchiveRoute;

$itemId = Factory::getApplication()->getInput()->getInt('Itemid');
?>
<div class="pungamail-newsletter-archive">
	<?php if ($this->showPageHeading) : ?>
		<h1><?php echo htmlspecialchars($this->pageHeading, ENT_QUOTES, 'UTF-8'); ?></h1>
	<?php endif; ?>
	<?php if ($this->intro !== '') : ?>
		<div class="mb-4"><?php echo nl2br(htmlspecialchars($this->intro, ENT_QUOTES, 'UTF-8')); ?></div>
	<?php endif; ?>

	<?php if ($this->items === []) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_PUNGAMAIL_ARCHIVE_EMPTY'); ?></div>
	<?php else : ?>
		<div class="list-group mb-4">
		<?php foreach ($this->items as $item) : ?>
			<?php $title = trim((string) $item->snapshot_subject) !== '' ? (string) $item->snapshot_subject : (string) $item->title; ?>
			<a class="list-group-item list-group-item-action py-3" href="<?php echo ArchiveRoute::itemUrl((int) $item->id, $itemId); ?>">
				<div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
					<div class="fw-semibold"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
					<?php if ($this->showSentDate && !empty($item->sent_at)) : ?><time class="small text-muted" datetime="<?php echo htmlspecialchars((string) $item->sent_at, ENT_QUOTES, 'UTF-8'); ?>"><?php echo HTMLHelper::_('date', $item->sent_at, Text::_('DATE_FORMAT_LC3')); ?></time><?php endif; ?>
				</div>
				<?php if ($this->showChannels && !empty($item->topics)) : ?><div class="small text-muted mt-1"><?php echo htmlspecialchars(implode(', ', (array) $item->topics), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
			</a>
		<?php endforeach; ?>
		</div>
		<?php if ($this->pagination !== null && $this->pagination->pagesTotal > 1) : ?>
			<nav class="pagination__wrapper" aria-label="<?php echo htmlspecialchars(Text::_('JLIB_HTML_PAGINATION'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $this->pagination->getPagesLinks(); ?></nav>
		<?php endif; ?>
	<?php endif; ?>
</div>
