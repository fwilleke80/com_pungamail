<?php
/** @var \Punga\Component\PungaMail\Administrator\View\Topic\HtmlView $this */
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper; use Joomla\CMS\Language\Text; use Joomla\CMS\Router\Route; use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;
$item = $this->item;
?>
<form action="<?php echo Route::_(AdministratorRoute::topics()); ?>" method="post" name="adminForm" id="adminForm">
	<div class="card"><div class="card-body">
		<div class="mb-3"><label class="form-label" for="topic-title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label><input class="form-control" required id="topic-title" name="title" value="<?php echo htmlspecialchars((string) ($item->title ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
		<div class="mb-3"><label class="form-label" for="topic-alias"><?php echo Text::_('JFIELD_ALIAS_LABEL'); ?></label><input class="form-control" id="topic-alias" name="alias" value="<?php echo htmlspecialchars((string) ($item->alias ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><div class="form-text"><?php echo Text::_('COM_PUNGAMAIL_TOPIC_ALIAS_HELP'); ?></div></div>
		<div class="mb-3"><label class="form-label" for="topic-description"><?php echo Text::_('JGLOBAL_DESCRIPTION'); ?></label><textarea class="form-control" id="topic-description" name="description" rows="5"><?php echo htmlspecialchars((string) ($item->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
	</div></div><input type="hidden" name="id" value="<?php echo (int) ($item->id ?? 0); ?>"><input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?>
</form>
