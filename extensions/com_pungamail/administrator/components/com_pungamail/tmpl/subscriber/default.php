<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Subscriber\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

?>
<form action="<?php echo Route::_('index.php?option=com_pungamail&view=subscribers&screen=subscriber'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="card">
		<div class="card-body">
			<p class="text-muted"><?php echo Text::_('COM_PUNGAMAIL_ADD_SUBSCRIBER_HELP'); ?></p>
			<?php echo $this->form?->renderField('recipient_type') ?? ''; ?>
			<?php echo $this->form?->renderField('email') ?? ''; ?>
			<?php echo $this->form?->renderField('user_id') ?? ''; ?>
		</div>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
