<?php
/**
 * @package Punga.Mail
 * @license MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Language\Language;

/** Isolated Joomla language catalog with Website overrides in every execution context. */
final class WebsiteLanguage extends Language
{
	/**
	 * Joomla's parent constructor reads overrides from JPATH_BASE, which points
	 * at the administrator during previews. Discard that catalog and use only
	 * Website overrides; retain Joomla's normal file loading and fallback logic.
	 *
	 * @param string $tag Default website language tag.
	 */
	public function __construct(string $tag)
	{
		parent::__construct($tag, false);
		$this->strings = [];
		$this->override = $this->parse(JPATH_SITE . '/language/overrides/' . $tag . '.override.ini');
		$this->strings = $this->override;
	}
}
