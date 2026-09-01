<?php
/** @var \Punga\Component\PungaMail\Site\View\Subscription\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$state = $this->subscriptionState;
$returnUrl = base64_encode(Uri::getInstance()->toString());
?>
<div class="pungamail-subscription-page">
	<h1><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_PAGE_HEADING'); ?></h1>
	<p><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_PAGE_INTRO'); ?></p>

	<?php if (!$state['logged_in']) : ?>
		<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.request'); ?>" method="post" class="pungamail-subscription-form">
			<div class="mb-3">
				<label class="form-label" for="pungamail-subscription-email"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_EMAIL'); ?></label>
				<input class="form-control" type="email" required autocomplete="email" id="pungamail-subscription-email" name="email">
			</div>
			<div style="position:absolute;left:-10000px" aria-hidden="true">
				<label><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_HONEYPOT'); ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
			</div>
			<button class="btn btn-primary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUBSCRIBE'); ?></button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php else : ?>
		<p class="small text-muted"><?php echo htmlspecialchars((string) $state['email'], ENT_QUOTES, 'UTF-8'); ?></p>
		<?php if ($state['subscribed']) : ?>
			<p><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_CURRENTLY_SUBSCRIBED'); ?></p>
			<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.userPreference'); ?>" method="post">
				<input type="hidden" name="subscribed" value="0">
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<button class="btn btn-outline-secondary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_UNSUBSCRIBE'); ?></button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php else : ?>
			<p><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_CURRENTLY_NOT_SUBSCRIBED'); ?></p>
			<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.userPreference'); ?>" method="post">
				<input type="hidden" name="subscribed" value="1">
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<button class="btn btn-primary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUBSCRIBE'); ?></button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php endif; ?>
	<?php endif; ?>
</div>
