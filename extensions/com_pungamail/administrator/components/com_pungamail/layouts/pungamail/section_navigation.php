<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Layout
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Punga\Component\PungaMail\Administrator\Service\AdministratorRoute;

$section = (string) ($displayData['section'] ?? '');
$active = (string) ($displayData['active'] ?? '');
$items = [];

if ($section === 'audience')
{
	$items = [
		'subscribers' => [Text::_('COM_PUNGAMAIL_SUBMENU_SUBSCRIBERS'), AdministratorRoute::subscribers()],
		'topics' => [Text::_('COM_PUNGAMAIL_SUBMENU_TOPICS'), AdministratorRoute::topics()],
	];
}
elseif ($section === 'design')
{
	$items = [
		'templates' => [Text::_('COM_PUNGAMAIL_SUBMENU_TEMPLATES'), AdministratorRoute::templates()],
		'contentlayouts' => [Text::_('COM_PUNGAMAIL_SUBMENU_CONTENT_LAYOUTS'), AdministratorRoute::contentLayouts()],
	];
}

if ($items === [])
{
	return;
}
?>
<nav class="mb-4" aria-label="<?php echo htmlspecialchars(Text::_('COM_PUNGAMAIL_SECTION_NAVIGATION'), ENT_QUOTES, 'UTF-8'); ?>">
	<ul class="nav nav-tabs">
	<?php foreach ($items as $key => [$label, $url]) : ?>
		<li class="nav-item">
			<a class="nav-link<?php echo $active === $key ? ' active' : ''; ?>"<?php echo $active === $key ? ' aria-current="page"' : ''; ?> href="<?php echo Route::_($url); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
		</li>
	<?php endforeach; ?>
	</ul>
</nav>
