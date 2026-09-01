<?php
/** @var array<string,mixed> $pungamail */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$returnUrl = base64_encode(Uri::getInstance()->toString());
$intro = trim((string) $params->get('intro', ''));
$buttonLabel = trim((string) $params->get('button_label', ''));
$buttonLabel = $buttonLabel !== '' ? $buttonLabel : Text::_('MOD_PUNGAMAIL_SIGNUP_SUBSCRIBE');
?>
<div class="pungamail-signup">
	<?php if ($intro !== '') : ?>
		<p><?php echo nl2br(htmlspecialchars($intro, ENT_QUOTES, 'UTF-8')); ?></p>
	<?php endif; ?>

	<?php if (!$pungamail['logged_in']) : ?>
		<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.request'); ?>" method="post">
			<div class="mb-2">
				<label class="visually-hidden" for="pungamail-email-<?php echo (int) $module->id; ?>"><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_EMAIL'); ?></label>
				<input class="form-control" type="email" required autocomplete="email" id="pungamail-email-<?php echo (int) $module->id; ?>" name="email" placeholder="<?php echo htmlspecialchars(Text::_('MOD_PUNGAMAIL_SIGNUP_EMAIL'), ENT_QUOTES, 'UTF-8'); ?>">
			</div>
			<div style="position:absolute;left:-10000px" aria-hidden="true">
				<label><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_HONEYPOT'); ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
			</div>
			<button class="btn btn-primary" type="submit"><?php echo htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8'); ?></button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php else : ?>
		<p class="small mb-2"><?php echo htmlspecialchars((string) $pungamail['email'], ENT_QUOTES, 'UTF-8'); ?></p>
		<?php if ($pungamail['subscribed']) : ?>
			<p><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_SUBSCRIBED'); ?></p>
			<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.userPreference'); ?>" method="post">
				<input type="hidden" name="subscribed" value="0">
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<button class="btn btn-outline-secondary" type="submit"><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_UNSUBSCRIBE'); ?></button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php else : ?>
			<p><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_NOT_SUBSCRIBED'); ?></p>
			<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.userPreference'); ?>" method="post">
				<input type="hidden" name="subscribed" value="1">
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<button class="btn btn-primary" type="submit"><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_SUBSCRIBE'); ?></button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php endif; ?>
	<?php endif; ?>
</div>
