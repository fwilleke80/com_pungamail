<?php
/** @var \Punga\Component\PungaMail\Site\View\Unsubscribe\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$input = Factory::getApplication()->getInput();
$token = $input->getString('token');
?>
<div class="pungamail-unsubscribe">
<?php if ($this->subscriber === null) : ?>
	<h1>Unsubscribe link invalid</h1>
	<p>This unsubscribe link is not valid.</p>
<?php else : ?>
	<h1>Unsubscribe from newsletter?</h1>
	<p><strong><?php echo htmlspecialchars((string) $this->subscriber->email, ENT_QUOTES, 'UTF-8'); ?></strong> will no longer receive newsletters.</p>
	<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.unsubscribe'); ?>" method="post">
		<input type="hidden" name="id" value="<?php echo (int) $this->subscriber->id; ?>">
		<input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
		<button class="btn btn-danger" type="submit">Yes, unsubscribe</button>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
<?php endif; ?>
</div>
