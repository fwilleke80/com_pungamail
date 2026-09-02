<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Delivery\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$settings = $this->settings;
$diagnostics = $this->diagnostics;
$identity = Factory::getApplication()->getIdentity();
?>
<div class="row g-3">
	<div class="col-12 col-xl-7">
		<div class="card mb-3">
			<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_BOUNCE_MAILBOX'); ?></strong></div>
			<div class="card-body">
				<p><?php echo Text::_('COM_PUNGAMAIL_BOUNCE_SETTINGS_IN_OPTIONS'); ?></p>
				<dl class="row mb-3">
					<dt class="col-sm-4"><?php echo Text::_('COM_PUNGAMAIL_CONFIGURATION_STATUS'); ?></dt>
					<dd class="col-sm-8"><?php echo trim((string) $settings->bounce_host) !== '' && $settings->password_configured ? Text::_('COM_PUNGAMAIL_CONFIGURED') : Text::_('COM_PUNGAMAIL_NOT_CONFIGURED'); ?></dd>
					<dt class="col-sm-4"><?php echo Text::_('COM_PUNGAMAIL_SERVER'); ?></dt>
					<dd class="col-sm-8"><code><?php echo htmlspecialchars((string) ($settings->bounce_host ?: '—'), ENT_QUOTES, 'UTF-8'); ?></code></dd>
				</dl>
				<div class="d-flex flex-wrap gap-2">
					<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_pungamail'); ?>"><?php echo Text::_('COM_PUNGAMAIL_OPEN_COMPONENT_OPTIONS'); ?></a>
					<form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post">
						<input type="hidden" name="task" value="delivery.processBounces">
						<button class="btn btn-outline-secondary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_PROCESS_BOUNCES_NOW'); ?></button>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_RECENT_BOUNCES'); ?></strong></div>
			<div class="table-responsive"><table class="table mb-0"><thead><tr><th><?php echo Text::_('JDATE'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_EMAIL'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_CLASSIFICATION'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_SMTP_STATUS'); ?></th><th><?php echo Text::_('COM_PUNGAMAIL_DETAILS'); ?></th></tr></thead><tbody>
			<?php foreach ($this->bounces as $bounce) : ?>
				<tr><td><?php echo HTMLHelper::_('date', $bounce->occurred_at, Text::_('DATE_FORMAT_LC5'), 'UTC'); ?></td><td><code><?php echo htmlspecialchars((string) $bounce->email, ENT_QUOTES, 'UTF-8'); ?></code><?php if ($bounce->suppression_reason) : ?><div class="badge bg-danger"><?php echo Text::_('COM_PUNGAMAIL_SUPPRESSED'); ?></div><?php endif; ?></td><td><?php echo htmlspecialchars((string) $bounce->classification, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($bounce->status_code ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td><td class="small"><?php echo htmlspecialchars((string) ($bounce->diagnostic ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
			<?php endforeach; ?>
			<?php if ($this->bounces === []) : ?><tr><td colspan="5" class="text-center text-muted py-4"><?php echo Text::_('COM_PUNGAMAIL_NO_BOUNCES'); ?></td></tr><?php endif; ?>
			</tbody></table></div>
		</div>
	</div>
	<div class="col-12 col-xl-5">
		<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_QUEUE_CONTROL'); ?></strong></div><div class="card-body"><p><?php echo Text::_($diagnostics['queue_paused'] ? 'COM_PUNGAMAIL_QUEUE_IS_PAUSED' : 'COM_PUNGAMAIL_QUEUE_IS_RUNNING'); ?></p><form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post"><input type="hidden" name="task" value="delivery.toggleQueue"><input type="hidden" name="paused" value="<?php echo $diagnostics['queue_paused'] ? 0 : 1; ?>"><button class="btn <?php echo $diagnostics['queue_paused'] ? 'btn-success' : 'btn-warning'; ?>" type="submit"><?php echo Text::_($diagnostics['queue_paused'] ? 'COM_PUNGAMAIL_RESUME_QUEUE' : 'COM_PUNGAMAIL_PAUSE_QUEUE'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form></div></div>
		<div class="card mb-3"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_MAIL_TEST'); ?></strong></div><div class="card-body"><form action="<?php echo Route::_('index.php?option=com_pungamail'); ?>" method="post"><label class="form-label" for="test-email"><?php echo Text::_('COM_PUNGAMAIL_RECIPIENT_EMAIL'); ?></label><div class="input-group"><input required class="form-control" id="test-email" name="test_email" type="email" value="<?php echo htmlspecialchars((string) $identity->email, ENT_QUOTES, 'UTF-8'); ?>"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_PUNGAMAIL_SEND_TEST_MAIL'); ?></button></div><input type="hidden" name="task" value="delivery.sendTest"><?php echo HTMLHelper::_('form.token'); ?></form></div></div>
		<div class="card"><div class="card-header"><strong><?php echo Text::_('COM_PUNGAMAIL_DIAGNOSTICS'); ?></strong></div><div class="card-body"><dl class="row mb-0"><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_JOOMLA_MAILER'); ?></dt><dd class="col-6"><code><?php echo htmlspecialchars((string) $diagnostics['mailer'], ENT_QUOTES, 'UTF-8'); ?></code></dd><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_SENDER'); ?></dt><dd class="col-6"><?php echo htmlspecialchars((string) $diagnostics['sender_email'], ENT_QUOTES, 'UTF-8'); ?> <?php echo $diagnostics['sender_valid'] ? '✓' : '⚠'; ?></dd><dt class="col-6">PHP IMAP</dt><dd class="col-6"><?php echo Text::_($diagnostics['imap_available'] ? 'JYES' : 'JNO'); ?></dd><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_CONFIG_BATCH_SIZE'); ?></dt><dd class="col-6"><?php echo (int) $diagnostics['batch_size']; ?></dd><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_CONFIG_MAX_ATTEMPTS'); ?></dt><dd class="col-6"><?php echo (int) $diagnostics['max_attempts']; ?></dd><dt class="col-6"><?php echo Text::_('COM_PUNGAMAIL_CONFIG_RETRY_MINUTES'); ?></dt><dd class="col-6"><?php echo (int) $diagnostics['retry_minutes']; ?></dd></dl><hr><p class="small text-muted mb-0"><?php echo Text::_('COM_PUNGAMAIL_DNS_GUIDANCE'); ?></p></div></div>
	</div>
</div>
