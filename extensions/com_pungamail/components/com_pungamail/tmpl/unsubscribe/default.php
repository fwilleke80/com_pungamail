<?php
/** @var \Punga\Component\PungaMail\Site\View\Unsubscribe\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$input = Factory::getApplication()->getInput();
$token = $input->getString('token');
$newsletterId = $input->getInt('mid');
?>
<div class="pungamail-unsubscribe">
<?php if ($this->subscriber === null) : ?>
	<h1><?php echo Text::_('COM_PUNGAMAIL_UNSUBSCRIBE_INVALID_TITLE'); ?></h1>
	<p><?php echo Text::_('COM_PUNGAMAIL_UNSUBSCRIBE_INVALID_TEXT'); ?></p>
<?php else : ?>
	<h1><?php echo Text::_('COM_PUNGAMAIL_UNSUBSCRIBE_TITLE'); ?></h1>
	<p><?php echo Text::sprintf('COM_PUNGAMAIL_UNSUBSCRIBE_TEXT', '<strong>' . htmlspecialchars((string) $this->subscriber->email, ENT_QUOTES, 'UTF-8') . '</strong>'); ?></p>
	<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.unsubscribe'); ?>" method="post">
		<input type="hidden" name="id" value="<?php echo (int) $this->subscriber->id; ?>">
		<input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
		<input type="hidden" name="mid" value="<?php echo $newsletterId; ?>">
		<button class="btn btn-danger" type="submit"><?php echo Text::_('COM_PUNGAMAIL_UNSUBSCRIBE_BUTTON'); ?></button>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
<?php endif; ?>
</div>
