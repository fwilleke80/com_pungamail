<?php
/**
 * @package     Punga.Mail
 * @subpackage  Plugin.User
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Plugin\User\PungaMail\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;
use Punga\Component\PungaMail\Administrator\Service\TopicRepository;

/** Channel choices filtered by the Joomla account being edited. */
final class ChannelsField extends ListField
{
	/** @var string */
	protected $type = 'Channels';

	/** @return array<int,object> */
	protected function getOptions(): array
	{
		$app = Factory::getApplication();
		$userId = $app->getInput()->getInt('id', 0);

		if ($userId <= 0 && !$app->isClient('administrator'))
		{
			$userId = (int) $app->getIdentity()->id;
		}

		$topics = ServiceFactory::topics()->active();

		if ($userId > 0)
		{
			$eligible = array_flip(ServiceFactory::topics()->eligibleIds(array_map(static fn (object $topic): int => (int) $topic->id, $topics), $userId, true));
			$topics = array_values(array_filter($topics, static fn (object $topic): bool => isset($eligible[(int) $topic->id])));
		}
		else
		{
			// During registration the account does not exist yet. Public and
			// registered-user Channels are safe to offer; group-only Channels are not.
			$topics = array_values(array_filter($topics, static fn (object $topic): bool => (string) ($topic->audience_mode ?? TopicRepository::AUDIENCE_EVERYONE) !== TopicRepository::AUDIENCE_GROUPS));
		}

		$options = parent::getOptions();

		foreach ($topics as $topic)
		{
			$options[] = HTMLHelper::_('select.option', (int) $topic->id, (string) $topic->title);
		}

		return $options;
	}
}
