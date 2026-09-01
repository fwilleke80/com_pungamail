<?php
/** @var \Punga\Component\PungaMail\Site\View\Message\HtmlView $this */

defined('_JEXEC') or die;

$messages = [
	'requested' => ['Check your email', 'If the address is eligible for subscription, a confirmation email has been sent.'],
	'confirmed' => ['Subscription confirmed', 'Your email address is now subscribed to the newsletter.'],
	'unsubscribed' => ['Unsubscribed', 'Your email address has been removed from newsletter delivery.'],
	'invalid_confirmation' => ['Confirmation failed', 'The confirmation link is invalid or has expired. Please request a new one.'],
	'invalid_unsubscribe' => ['Unsubscribe failed', 'The unsubscribe link is invalid.'],
];
$message = $messages[$this->type] ?? $messages['requested'];
?>
<div class="pungamail-message">
	<h1><?php echo htmlspecialchars($message[0], ENT_QUOTES, 'UTF-8'); ?></h1>
	<p><?php echo htmlspecialchars($message[1], ENT_QUOTES, 'UTF-8'); ?></p>
</div>
