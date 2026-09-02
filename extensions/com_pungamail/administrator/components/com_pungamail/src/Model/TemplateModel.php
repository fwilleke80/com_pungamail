<?php
/** @package Punga.Mail @subpackage Administrator.Model */
namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Template editor model. */
final class TemplateModel extends BaseDatabaseModel
{
	private bool $itemLoaded = false;
	private ?object $item = null;

	/** @return object|null */
	public function getItem(): ?object
	{
		if ($this->itemLoaded)
		{
			return $this->item;
		}

		$this->itemLoaded = true;
		$id = Factory::getApplication()->getInput()->getInt('id');
		$this->item = $id > 0 ? ServiceFactory::templates()->find($id) : null;

		if ($this->item !== null)
		{
			ServiceFactory::checkouts()->checkout('template', $id, (int) Factory::getApplication()->getIdentity()->id);
			$this->item = ServiceFactory::templates()->find($id);
		}

		return $this->item;
	}

	/** @return array<string,string> */
	public function getStyleOverrides(): array
	{
		$item = $this->getItem();
		return ServiceFactory::styles()->decode($item?->style_overrides ?? null);
	}
}
