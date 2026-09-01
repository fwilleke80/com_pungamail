<?php
/** @package Punga.Mail @subpackage Administrator */
namespace Punga\Component\PungaMail\Administrator\Extension;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;

/** Punga Mail component extension. */
final class PungaMailComponent extends MVCComponent implements RouterServiceInterface
{
	use RouterServiceTrait;
}
