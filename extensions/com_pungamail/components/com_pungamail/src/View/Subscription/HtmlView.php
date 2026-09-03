<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\View\Subscription;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Public subscription page view.
 */
final class HtmlView extends BaseHtmlView
{
	/** @var array{logged_in:bool,email:string,subscribed:bool,topics:array<int,object>,selected_topic_ids:array<int,int>} */
	public array $subscriptionState = [];

	/**
	 * Loads state before rendering.
	 *
	 * @param string|null $tpl Layout name.
	 *
	 * @return void
	 */
	public function display($tpl = null): void
	{
		$this->subscriptionState = $this->getModel()->getSubscriptionState();
		parent::display($tpl);
	}
}
