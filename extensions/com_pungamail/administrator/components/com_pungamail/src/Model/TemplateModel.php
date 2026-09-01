<?php
/** @package Punga.Mail @subpackage Administrator.Model */
namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Template editor model. */
final class TemplateModel extends BaseDatabaseModel
{
	/** @return object|null */
	public function getItem(): ?object
	{
		$id = Factory::getApplication()->getInput()->getInt('id');
		return $id > 0 ? ServiceFactory::templates()->find($id) : null;
	}

	/** @return array<string,string> */
	public function getStyleOverrides(): array
	{
		$item = $this->getItem();
		return ServiceFactory::styles()->decode($item?->style_overrides ?? null);
	}
}
