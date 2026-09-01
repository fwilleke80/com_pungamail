<?php
/** @var \Punga\Component\PungaMail\Site\View\Confirm\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$token = Factory::getApplication()->getInput()->getString('token');
?>
<div class="pungamail-confirm">
<?php if ($this->subscriber === null) : ?>
	<h1>Confirmation link invalid or expired</h1>
	<p>This confirmation request cannot be used. Please submit the newsletter signup form again.</p>
<?php else : ?>
	<h1>Confirm newsletter subscription</h1>
	<p>Confirm that <strong><?php echo htmlspecialchars((string) $this->subscriber->email, ENT_QUOTES, 'UTF-8'); ?></strong> should receive the newsletter.</p>
	<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.confirm'); ?>" method="post">
		<input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
		<button class="btn btn-primary" type="submit">Confirm subscription</button>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
<?php endif; ?>
</div>
