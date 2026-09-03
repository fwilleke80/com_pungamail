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
$topics = (array) ($pungamail['topics'] ?? []);
$selectedTopicIds = array_map('intval', (array) ($pungamail['selected_topic_ids'] ?? []));
$singleTopicMode = (bool) ($pungamail['single_topic_mode'] ?? false);
?>
<div class="pungamail-signup">
	<?php if ($intro !== '') : ?>
		<p><?php echo nl2br(htmlspecialchars($intro, ENT_QUOTES, 'UTF-8')); ?></p>
	<?php endif; ?>

	<?php if (!$pungamail['logged_in']) : ?>
		<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.request'); ?>" method="post">
			<input type="hidden" name="module_id" value="<?php echo (int) $module->id; ?>">
			<div class="mb-2">
				<label class="visually-hidden" for="pungamail-email-<?php echo (int) $module->id; ?>"><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_EMAIL'); ?></label>
				<input class="form-control" type="email" required autocomplete="email" id="pungamail-email-<?php echo (int) $module->id; ?>" name="email" placeholder="<?php echo htmlspecialchars(Text::_('MOD_PUNGAMAIL_SIGNUP_EMAIL'), ENT_QUOTES, 'UTF-8'); ?>">
			</div>
			<div style="position:absolute;left:-10000px" aria-hidden="true">
				<label><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_HONEYPOT'); ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
			</div>
			<?php if ($singleTopicMode && count($topics) === 1) : ?>
				<input type="hidden" name="topic_ids[]" value="<?php echo (int) $topics[0]->id; ?>">
				<p class="small"><?php echo Text::sprintf('MOD_PUNGAMAIL_SIGNUP_SINGLE_TOPIC', '<strong>' . htmlspecialchars((string) $topics[0]->title, ENT_QUOTES, 'UTF-8') . '</strong>'); ?></p>
			<?php elseif ($topics !== []) : ?>
				<fieldset class="mb-3"><legend class="h6"><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_CHOOSE_TOPICS'); ?></legend>
				<?php foreach ($topics as $topic) : ?><div class="form-check"><input class="form-check-input" type="checkbox" name="topic_ids[]" value="<?php echo (int) $topic->id; ?>" id="pungamail-topic-<?php echo (int) $module->id; ?>-<?php echo (int) $topic->id; ?>"><label class="form-check-label" for="pungamail-topic-<?php echo (int) $module->id; ?>-<?php echo (int) $topic->id; ?>"><?php echo htmlspecialchars((string) $topic->title, ENT_QUOTES, 'UTF-8'); ?></label><?php if (trim((string) $topic->description) !== '') : ?><div class="form-text"><?php echo htmlspecialchars((string) $topic->description, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?></div><?php endforeach; ?>
				</fieldset>
			<?php endif; ?>
			<button class="btn btn-primary" type="submit"><?php echo htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8'); ?></button>
			<?php if ($topics !== []) : ?><div class="form-text"><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_TOPIC_CONFIRM_HELP'); ?></div><?php endif; ?>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php else : ?>
		<p class="small mb-2"><?php echo htmlspecialchars((string) $pungamail['email'], ENT_QUOTES, 'UTF-8'); ?></p>
		<?php if ($topics !== []) : ?>
			<form class="mb-3" action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.userTopics'); ?>" method="post">
				<input type="hidden" name="module_id" value="<?php echo (int) $module->id; ?>">
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<?php if ($singleTopicMode && count($topics) === 1) : $topic = $topics[0]; $selected = in_array((int) $topic->id, $selectedTopicIds, true); ?>
					<p><?php echo Text::sprintf('MOD_PUNGAMAIL_SIGNUP_SINGLE_TOPIC', '<strong>' . htmlspecialchars((string) $topic->title, ENT_QUOTES, 'UTF-8') . '</strong>'); ?></p>
					<?php if (!$selected) : ?><input type="hidden" name="topic_ids[]" value="<?php echo (int) $topic->id; ?>"><?php endif; ?>
					<button class="btn <?php echo $selected ? 'btn-outline-secondary' : 'btn-primary'; ?>" type="submit"><?php echo Text::_($selected ? 'MOD_PUNGAMAIL_SIGNUP_UNSUBSCRIBE_TOPIC' : 'MOD_PUNGAMAIL_SIGNUP_SUBSCRIBE_TOPIC'); ?></button>
				<?php else : ?>
					<fieldset class="mb-2"><legend class="h6"><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_TOPIC_PREFERENCES'); ?></legend><?php foreach ($topics as $topic) : ?><div class="form-check"><input class="form-check-input" type="checkbox" name="topic_ids[]" value="<?php echo (int) $topic->id; ?>" id="pungamail-user-topic-<?php echo (int) $module->id; ?>-<?php echo (int) $topic->id; ?>" <?php echo in_array((int) $topic->id, $selectedTopicIds, true) ? 'checked' : ''; ?>><label class="form-check-label" for="pungamail-user-topic-<?php echo (int) $module->id; ?>-<?php echo (int) $topic->id; ?>"><?php echo htmlspecialchars((string) $topic->title, ENT_QUOTES, 'UTF-8'); ?></label></div><?php endforeach; ?></fieldset>
					<button class="btn btn-primary" type="submit"><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_SAVE_TOPICS'); ?></button>
				<?php endif; ?>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php endif; ?>
		<?php if ($pungamail['subscribed']) : ?>
			<p><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_SUBSCRIBED'); ?></p>
			<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.userPreference'); ?>" method="post">
				<input type="hidden" name="subscribed" value="0">
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<button class="btn btn-danger" type="submit"><?php echo Text::_('MOD_PUNGAMAIL_SIGNUP_UNSUBSCRIBE_COMPLETELY'); ?></button>
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
