<?php
/** @package Punga.Mail @subpackage Administrator.Model */
namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Punga\Component\PungaMail\Administrator\Service\RecipientName;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Browser preview model for the next Automatic Newsletter. */
final class DigestpreviewModel extends BaseDatabaseModel
{
	/** @return array<string,mixed> */
	public function getData(): array
	{
		$id = max(0, Factory::getApplication()->getInput()->getInt('id', 0));
		$identity = Factory::getApplication()->getIdentity();
		$result = ServiceFactory::digestProcessor()->preview(
			$id,
			RecipientName::resolve((string) $identity->name, (string) $identity->email),
			(int) $identity->id
		);
		$result['digest'] = ServiceFactory::digests()->find($id);

		return $result;
	}
}
