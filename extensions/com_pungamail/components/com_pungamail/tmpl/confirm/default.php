<?php
/** @var \Punga\Component\PungaMail\Site\View\Confirm\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$token = Factory::getApplication()->getInput()->getString('token');
$kind = Factory::getApplication()->getInput()->getCmd('kind');
?>
<div class="pungamail-confirm">
<?php if ($this->subscriber === null) : ?>
	<h1><?php echo Text::_('COM_PUNGAMAIL_CONFIRM_INVALID_TITLE'); ?></h1>
	<p><?php echo Text::_('COM_PUNGAMAIL_CONFIRM_INVALID_TEXT'); ?></p>
<?php else : ?>
	<h1><?php echo Text::_($kind === 'topics' ? 'COM_PUNGAMAIL_CONFIRM_TOPICS_TITLE' : 'COM_PUNGAMAIL_CONFIRM_TITLE'); ?></h1>
	<p><?php echo Text::sprintf($kind === 'topics' ? 'COM_PUNGAMAIL_CONFIRM_TOPICS_TEXT' : 'COM_PUNGAMAIL_CONFIRM_TEXT', '<strong>' . htmlspecialchars((string) $this->subscriber->email, ENT_QUOTES, 'UTF-8') . '</strong>'); ?></p>
	<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.confirm'); ?>" method="post">
		<input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
		<input type="hidden" name="kind" value="<?php echo htmlspecialchars($kind, ENT_QUOTES, 'UTF-8'); ?>">
		<button class="btn btn-primary" type="submit"><?php echo Text::_($kind === 'topics' ? 'COM_PUNGAMAIL_CONFIRM_TOPICS_BUTTON' : 'COM_PUNGAMAIL_CONFIRM_BUTTON'); ?></button>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
<?php endif; ?>
</div>
