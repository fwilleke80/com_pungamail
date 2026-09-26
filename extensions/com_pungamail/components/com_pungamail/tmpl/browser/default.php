<?php
/** @var \Punga\Component\PungaMail\Site\View\Browser\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Punga\Component\PungaMail\Site\Service\PublicSnapshot;

if ($this->newsletter === null)
{
	echo '<div class="alert alert-warning"><h1>' . Text::_('COM_PUNGAMAIL_BROWSER_NOT_FOUND_TITLE') . '</h1><p>' . Text::_('COM_PUNGAMAIL_BROWSER_NOT_FOUND_TEXT') . '</p></div>';
	return;
}

$html = PublicSnapshot::browserHtml((string) $this->newsletter->snapshot_html);
?>
<iframe sandbox="" title="<?php echo htmlspecialchars((string) $this->newsletter->snapshot_subject, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;min-height:80vh;border:0;background:#fff" srcdoc="<?php echo htmlspecialchars($html, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
