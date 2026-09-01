<?php
/** @var \Punga\Component\PungaMail\Site\View\Message\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$messages = [
	'requested' => ['COM_PUNGAMAIL_MESSAGE_REQUESTED_TITLE', 'COM_PUNGAMAIL_MESSAGE_REQUESTED_TEXT'],
	'confirmed' => ['COM_PUNGAMAIL_MESSAGE_CONFIRMED_TITLE', 'COM_PUNGAMAIL_MESSAGE_CONFIRMED_TEXT'],
	'unsubscribed' => ['COM_PUNGAMAIL_MESSAGE_UNSUBSCRIBED_TITLE', 'COM_PUNGAMAIL_MESSAGE_UNSUBSCRIBED_TEXT'],
	'invalid_confirmation' => ['COM_PUNGAMAIL_MESSAGE_INVALID_CONFIRMATION_TITLE', 'COM_PUNGAMAIL_MESSAGE_INVALID_CONFIRMATION_TEXT'],
	'invalid_unsubscribe' => ['COM_PUNGAMAIL_MESSAGE_INVALID_UNSUBSCRIBE_TITLE', 'COM_PUNGAMAIL_MESSAGE_INVALID_UNSUBSCRIBE_TEXT'],
];
$message = $messages[$this->type] ?? $messages['requested'];
?>
<div class="pungamail-message">
	<h1><?php echo Text::_($message[0]); ?></h1>
	<p><?php echo Text::_($message[1]); ?></p>
</div>
