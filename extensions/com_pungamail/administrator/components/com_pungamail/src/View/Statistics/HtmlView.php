<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.View
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\View\Statistics;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Punga\Component\PungaMail\Administrator\Service\Permissions;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Privacy-conscious Newsletter and campaign statistics. */
final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $dashboard = [];

	/** @var array<string,mixed>|null */
	public ?array $report = null;

	public int $days = 90;
	public int $newsletterId = 0;
	public string $siteTimezone = 'UTC';

	/** @return void */
	public function display($tpl = null): void
	{
		Permissions::require(Permissions::VIEW_STATISTICS);

		$app = Factory::getApplication();
		$this->days = $app->getInput()->getInt('days', 90);
		$this->days = in_array($this->days, [0, 7, 30, 90, 365], true) ? $this->days : 90;
		$this->newsletterId = max(0, $app->getInput()->getInt('newsletter_id', 0));
		$this->siteTimezone = (string) $app->get('offset', 'UTC');

		$statistics = ServiceFactory::statistics();
		if ($this->newsletterId > 0)
		{
			$this->report = $statistics->campaignReport($this->newsletterId);
			if ($this->report === null)
			{
				throw new \RuntimeException(Text::_('COM_PUNGAMAIL_STATISTICS_NEWSLETTER_NOT_FOUND'), 404);
			}
		}
		else
		{
			$this->dashboard = $statistics->campaignDashboard($this->days);
		}

		ToolbarHelper::title(Text::_('COM_PUNGAMAIL_STATISTICS'), 'chart');
		if (Permissions::canConfigure())
		{
			ToolbarHelper::preferences('com_pungamail');
		}
		parent::display($tpl);
	}
}
