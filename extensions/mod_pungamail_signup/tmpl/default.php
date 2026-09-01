<?php
/** @var array<string,mixed> $pungamail */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$returnUrl = base64_encode(Uri::getInstance()->toString());
?>
<div class="pungamail-signup">
	<?php if ((string) $params->get('intro', '') !== '') : ?>
		<p><?php echo nl2br(htmlspecialchars((string) $params->get('intro'), ENT_QUOTES, 'UTF-8')); ?></p>
	<?php endif; ?>

	<?php if (!$pungamail['logged_in']) : ?>
		<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.request'); ?>" method="post">
			<div class="mb-2">
				<label class="visually-hidden" for="pungamail-email-<?php echo (int) $module->id; ?>">Email address</label>
				<input class="form-control" type="email" required autocomplete="email" id="pungamail-email-<?php echo (int) $module->id; ?>" name="email" placeholder="Email address">
			</div>
			<div style="position:absolute;left:-10000px" aria-hidden="true">
				<label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
			</div>
			<button class="btn btn-primary" type="submit"><?php echo htmlspecialchars((string) $params->get('button_label', 'Subscribe'), ENT_QUOTES, 'UTF-8'); ?></button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php else : ?>
		<p class="small mb-2"><?php echo htmlspecialchars((string) $pungamail['email'], ENT_QUOTES, 'UTF-8'); ?></p>
		<?php if ($pungamail['subscribed']) : ?>
			<p>You are subscribed.</p>
			<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.userPreference'); ?>" method="post">
				<input type="hidden" name="subscribed" value="0">
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<button class="btn btn-outline-secondary" type="submit">Unsubscribe</button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php else : ?>
			<p>You are not subscribed.</p>
			<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.userPreference'); ?>" method="post">
				<input type="hidden" name="subscribed" value="1">
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<button class="btn btn-primary" type="submit">Subscribe</button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php endif; ?>
	<?php endif; ?>
</div>
