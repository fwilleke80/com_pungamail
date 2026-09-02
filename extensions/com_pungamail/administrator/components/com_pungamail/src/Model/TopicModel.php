<?php
/** @package Punga.Mail @subpackage Administrator.Model */
namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Mailing-topic editor model. */
final class TopicModel extends BaseDatabaseModel
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
		$this->item = $id > 0 ? ServiceFactory::topics()->find($id) : null;

		if ($this->item !== null)
		{
			ServiceFactory::checkouts()->checkout('topic', $id, (int) Factory::getApplication()->getIdentity()->id);
			$this->item = ServiceFactory::topics()->find($id);
		}

		return $this->item;
	}
}
