<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\User\UserFactoryInterface;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/**
 * Form model for adding newsletter recipients from the administrator UI.
 */
final class SubscriberModel extends FormModel
{
	/**
	 * Loads the subscriber-add form.
	 *
	 * @param array<string,mixed> $data Form data.
	 * @param bool $loadData Whether to load model state.
	 *
	 * @return Form|null Form instance.
	 */
	public function getForm($data = [], $loadData = true): ?Form
	{
		$form = $this->loadForm(
			'com_pungamail.subscriber',
			'subscriber',
			['control' => 'jform', 'load_data' => $loadData]
		);

		if ($form !== null && Factory::getApplication()->getInput()->getInt('id') > 0)
		{
			foreach (['recipient_type', 'email', 'user_id'] as $field)
			{
				$form->setFieldAttribute($field, 'disabled', 'true');
			}
		}

		return $form;
	}

	/** @return object|null */
	public function getItem(): ?object
	{
		$id = Factory::getApplication()->getInput()->getInt('id');

		if ($id <= 0)
		{
			return null;
		}

		$item = ServiceFactory::subscribers()->findById($id);

		if ($item !== null && $item->user_id !== null)
		{
			$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $item->user_id);
			$item->user_name = (int) $user->id > 0 ? (string) $user->name : '';
		}

		return $item;
	}

	/** @return array<int,object> */
	public function getTopics(): array
	{
		$item = $this->getItem();
		$userId = $item !== null && $item->user_id !== null ? (int) $item->user_id : null;

		return ServiceFactory::topics()->annotateEligibility(ServiceFactory::topics()->availableForAdministration(), $userId);
	}

	/** @return array<int,int> */
	public function getSelectedTopicIds(): array
	{
		$item = $this->getItem();

		return $item !== null ? ServiceFactory::topics()->getSubscriberTopicIds((int) $item->id) : [];
	}

	/**
	 * Returns the address-level delivery block for the current subscriber.
	 *
	 * @return string|null Suppression reason, or null when no block is active.
	 */
	public function getSuppressionReason(): ?string
	{
		$item = $this->getItem();

		return $item !== null
			? ServiceFactory::subscribers()->getSuppressionReason((string) $item->email_normalized)
			: null;
	}

	/** @return array<string,mixed> */
	protected function loadFormData(): array
	{
		$item = $this->getItem();

		if ($item === null)
		{
			return [
				'id' => 0,
				'recipient_type' => 'email',
				'status' => 1,
			];
		}

		return [
			'id' => (int) $item->id,
			'recipient_type' => $item->user_id !== null ? 'user' : 'email',
			'email' => (string) $item->email,
			'user_id' => (int) ($item->user_id ?? 0),
			'recipient_name' => (string) ($item->recipient_name ?? ''),
			'status' => (int) $item->status,
		];
	}
}
