<?php
/** @var \Punga\Component\PungaMail\Site\View\Archiveitem\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Site\Service\PublicSnapshot;

$item = $this->newsletter;
$title = trim((string) $item->snapshot_subject) !== '' ? (string) $item->snapshot_subject : (string) $item->title;
$html = PublicSnapshot::archiveHtml((string) $item->snapshot_html);
$itemId = Factory::getApplication()->getInput()->getInt('Itemid');
?>
<div class="pungamail-newsletter-archive-item">
	<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
		<div>
			<h1 class="mb-1"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
			<?php if (!empty($item->sent_at)) : ?><div class="text-muted small"><?php echo Text::sprintf('COM_PUNGAMAIL_ARCHIVE_SENT_ON', HTMLHelper::_('date', $item->sent_at, Text::_('DATE_FORMAT_LC3'))); ?></div><?php endif; ?>
			<?php if (!empty($item->topics)) : ?><div class="text-muted small"><?php echo htmlspecialchars(implode(', ', (array) $item->topics), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
		</div>
		<a class="btn btn-outline-secondary" href="<?php echo $itemId > 0 ? Route::_('index.php?option=com_pungamail&view=archive&Itemid=' . $itemId) : Route::_('index.php?option=com_pungamail&view=archive'); ?>"><?php echo Text::_('COM_PUNGAMAIL_ARCHIVE_BACK'); ?></a>
	</div>
	<iframe sandbox="" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;min-height:80vh;border:0;background:#fff" srcdoc="<?php echo htmlspecialchars($html, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
</div>
