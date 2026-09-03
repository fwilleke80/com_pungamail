<?php
/** @var \Punga\Component\PungaMail\Site\View\Subscription\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$state = $this->subscriptionState;
$returnUrl = base64_encode(Uri::getInstance()->toString());
$topics = (array) ($state['topics'] ?? []);
$selectedTopicIds = array_map('intval', (array) ($state['selected_topic_ids'] ?? []));
?>
<div class="pungamail-subscription-page">
	<h1><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_PAGE_HEADING'); ?></h1>
	<p><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_PAGE_INTRO'); ?></p>

	<?php if (!$state['logged_in']) : ?>
		<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.request'); ?>" method="post" class="pungamail-subscription-form">
			<div class="alert alert-info">
				<strong><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_DELIVERY_HEADING'); ?>:</strong>
				<?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_DELIVERY_AFTER_CONFIRMATION'); ?>
				<div class="small mt-1"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_MASTER_HELP'); ?></div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="pungamail-subscription-email"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_EMAIL'); ?></label>
				<input class="form-control" type="email" required autocomplete="email" id="pungamail-subscription-email" name="email">
			</div>
			<div style="position:absolute;left:-10000px" aria-hidden="true">
				<label><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_HONEYPOT'); ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
			</div>
			<?php if ($topics !== []) : ?>
				<fieldset class="mb-3">
					<legend class="h5"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_TOPICS_QUESTION'); ?></legend>
					<p class="form-text"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_TOPICS_SIGNUP_HELP'); ?></p>
					<?php foreach ($topics as $topic) : ?>
						<div class="form-check mb-2">
							<input class="form-check-input pm-subscription-topic" type="checkbox" name="topic_ids[]" value="<?php echo (int) $topic->id; ?>" id="pungamail-page-topic-<?php echo (int) $topic->id; ?>">
							<label class="form-check-label" for="pungamail-page-topic-<?php echo (int) $topic->id; ?>"><?php echo htmlspecialchars((string) $topic->title, ENT_QUOTES, 'UTF-8'); ?></label>
							<?php if (trim((string) $topic->description) !== '') : ?>
								<div class="form-text"><?php echo htmlspecialchars((string) $topic->description, ENT_QUOTES, 'UTF-8'); ?></div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<div class="alert alert-secondary small mt-3 pm-subscription-topic-summary" role="status"
						data-global-enabled="1"
						data-none="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUMMARY_NONE'), ENT_QUOTES, 'UTF-8'); ?>"
						data-selected="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUMMARY_SELECTED'), ENT_QUOTES, 'UTF-8'); ?>"
						data-disabled="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUMMARY_DISABLED'), ENT_QUOTES, 'UTF-8'); ?>"></div>
				</fieldset>
			<?php else : ?>
				<div class="alert alert-secondary small"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUMMARY_NONE'); ?></div>
			<?php endif; ?>
			<button class="btn btn-primary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUBSCRIBE'); ?></button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php else : ?>
		<p class="small text-muted"><?php echo htmlspecialchars((string) $state['email'], ENT_QUOTES, 'UTF-8'); ?></p>
		<div class="card mb-4">
			<div class="card-body">
				<h2 class="h5"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_DELIVERY_HEADING'); ?></h2>
				<p><span class="badge <?php echo $state['subscribed'] ? 'text-bg-success' : 'text-bg-secondary'; ?>"><?php echo Text::_($state['subscribed'] ? 'COM_PUNGAMAIL_SUBSCRIPTION_DELIVERY_ENABLED' : 'COM_PUNGAMAIL_SUBSCRIPTION_DELIVERY_DISABLED'); ?></span></p>
				<p class="small text-muted"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_MASTER_HELP'); ?></p>
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
			</div>
		</div>

		<?php if ($topics !== []) : ?>
			<form action="<?php echo Route::_('index.php?option=com_pungamail&task=subscription.userTopics'); ?>" method="post">
				<fieldset>
					<legend class="h5"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_TOPICS_QUESTION'); ?></legend>
					<p class="form-text"><?php echo Text::_($state['subscribed'] ? 'COM_PUNGAMAIL_SUBSCRIPTION_TOPICS_ACCOUNT_HELP' : 'COM_PUNGAMAIL_SUBSCRIPTION_TOPICS_DISABLED_HELP'); ?></p>
					<?php foreach ($topics as $topic) : ?>
						<div class="form-check mb-2">
							<input class="form-check-input pm-subscription-topic" type="checkbox" name="topic_ids[]" value="<?php echo (int) $topic->id; ?>" id="pungamail-account-topic-<?php echo (int) $topic->id; ?>" <?php echo in_array((int) $topic->id, $selectedTopicIds, true) ? 'checked' : ''; ?>>
							<label class="form-check-label" for="pungamail-account-topic-<?php echo (int) $topic->id; ?>"><?php echo htmlspecialchars((string) $topic->title, ENT_QUOTES, 'UTF-8'); ?></label>
							<?php if (trim((string) $topic->description) !== '') : ?>
								<div class="form-text"><?php echo htmlspecialchars((string) $topic->description, ENT_QUOTES, 'UTF-8'); ?></div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<div class="alert alert-secondary small mt-3 pm-subscription-topic-summary" role="status"
						data-global-enabled="<?php echo $state['subscribed'] ? '1' : '0'; ?>"
						data-none="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUMMARY_NONE'), ENT_QUOTES, 'UTF-8'); ?>"
						data-selected="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUMMARY_SELECTED'), ENT_QUOTES, 'UTF-8'); ?>"
						data-disabled="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUMMARY_DISABLED'), ENT_QUOTES, 'UTF-8'); ?>"></div>
				</fieldset>
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<button class="btn btn-primary mt-2" type="submit"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SAVE_TOPICS'); ?></button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php elseif ($state['subscribed']) : ?>
			<div class="alert alert-secondary small"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUMMARY_NONE'); ?></div>
		<?php else : ?>
			<div class="alert alert-secondary small"><?php echo Text::_('COM_PUNGAMAIL_SUBSCRIPTION_SUMMARY_DISABLED'); ?></div>
		<?php endif; ?>
	<?php endif; ?>
	<script>
	document.addEventListener('DOMContentLoaded', function ()
	{
		document.querySelectorAll('.pm-subscription-topic-summary').forEach(function (summary)
		{
			const form = summary.closest('form');
			const topics = form ? Array.from(form.querySelectorAll('.pm-subscription-topic')) : [];
			const update = function ()
			{
				if (summary.dataset.globalEnabled !== '1')
				{
					summary.textContent = summary.dataset.disabled || '';
					return;
				}

				const names = topics.filter(function (topic)
				{
					return topic.checked;
				}).map(function (topic)
				{
					const label = form.querySelector(`label[for="${topic.id}"]`);

					return label ? label.textContent.trim() : '';
				}).filter(Boolean);

				summary.textContent = names.length > 0
					? (summary.dataset.selected || '').replace('{names}', names.join(', '))
					: (summary.dataset.none || '');
			};

			topics.forEach(function (topic)
			{
				topic.addEventListener('change', update);
			});
			update();
		});
	});
	</script>
</div>
