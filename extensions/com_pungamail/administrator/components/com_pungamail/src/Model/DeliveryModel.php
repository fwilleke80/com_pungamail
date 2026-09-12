<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Model
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Mail delivery, bounce and queue diagnostics model. */
final class DeliveryModel extends BaseDatabaseModel
{
	/** @return object */
	public function getSettings(): object
	{
		return ServiceFactory::mailSettings()->getPublic();
	}

	/** @return object|null */
	public function getBounceCheck(): ?object
	{
		return ServiceFactory::mailSettings()->getBounceCheck();
	}

	/** @return array<int,object> */
	public function getBounces(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['b.*', 's.id AS current_subscriber_id', 's.soft_bounce_count AS current_soft_bounce_count', 'x.reason AS suppression_reason'])
			->from($db->quoteName('#__pungamail_bounces', 'b'))
			->leftJoin($db->quoteName('#__pungamail_subscribers', 's') . ' ON s.id = b.subscriber_id')
			->leftJoin($db->quoteName('#__pungamail_suppressions', 'x') . ' ON x.email_normalized = b.email_normalized')
			->order($db->quoteName('b.occurred_at') . ' DESC');

		return $db->setQuery($query, 0, 100)->loadObjectList();
	}

	/** @return array{status:string,newsletter_id:int,search:string,archive:string} */
	public function getQueueFilters(): array
	{
		$input = Factory::getApplication()->getInput();
		$status = $input->getCmd('queue_status', '');
		$allowedStatuses = ['pending', 'processing', 'sent', 'failed', 'cancelled', 'bounced'];
		$archive = $input->getCmd('queue_archive', 'active');
		$allowedArchiveFilters = ['active', 'archived', 'all'];

		return [
			'status' => in_array($status, $allowedStatuses, true) ? $status : '',
			'newsletter_id' => max(0, $input->getInt('queue_newsletter', 0)),
			'search' => trim($input->getString('queue_search')),
			'archive' => in_array($archive, $allowedArchiveFilters, true) ? $archive : 'active',
		];
	}

	/** @return array<int,object> */
	public function getQueue(): array
	{
		$db = $this->getDatabase();
		$filters = $this->getQueueFilters();
		$query = $db->getQuery(true)
			->select(['q.*', 'n.title AS newsletter_title'])
			->from($db->quoteName('#__pungamail_send_queue', 'q'))
			->leftJoin($db->quoteName('#__pungamail_newsletters', 'n') . ' ON n.id = q.newsletter_id');

		if ($filters['archive'] === 'active')
		{
			$query->where($db->quoteName('q.archived') . ' = 0');
		}
		elseif ($filters['archive'] === 'archived')
		{
			$query->where($db->quoteName('q.archived') . ' = 1');
		}

		if ($filters['status'] !== '')
		{
			$queueStatus = $filters['status'];
			$query->where($db->quoteName('q.status') . ' = :queueStatus')
				->bind(':queueStatus', $queueStatus);
		}

		if ($filters['newsletter_id'] > 0)
		{
			$queueNewsletter = $filters['newsletter_id'];
			$query->where($db->quoteName('q.newsletter_id') . ' = :queueNewsletter')
				->bind(':queueNewsletter', $queueNewsletter, ParameterType::INTEGER);
		}

		if ($filters['search'] !== '')
		{
			$search = '%' . $filters['search'] . '%';
			$query->where(
				'(' . $db->quoteName('q.email') . ' LIKE :queueSearchEmail'
				. ' OR ' . $db->quoteName('q.recipient_name') . ' LIKE :queueSearchName'
				. ' OR ' . $db->quoteName('n.title') . ' LIKE :queueSearchTitle)'
			)
				->bind(':queueSearchEmail', $search)
				->bind(':queueSearchName', $search)
				->bind(':queueSearchTitle', $search);
		}

		$query->order("CASE q.status WHEN 'processing' THEN 0 WHEN 'pending' THEN 1 WHEN 'failed' THEN 2 ELSE 3 END ASC")
			->order($db->quoteName('q.created') . ' DESC');

		return $db->setQuery($query, 0, 200)->loadObjectList();
	}

	/** @return array<int,object> */
	public function getQueueNewsletters(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('DISTINCT ' . $db->quoteName('n.id') . ', ' . $db->quoteName('n.title'))
			->from($db->quoteName('#__pungamail_newsletters', 'n'))
			->innerJoin($db->quoteName('#__pungamail_send_queue', 'q') . ' ON q.newsletter_id = n.id')
			->order($db->quoteName('n.title') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	/** @return array<string,mixed> */
	public function getDiagnostics(): array
	{
		$config = Factory::getConfig();
		$params = ComponentHelper::getParams('com_pungamail');
		$sender = ServiceFactory::mailConfiguration()->sender();
		$outgoing = ServiceFactory::mailSettings()->getOutgoingPublic();
		$customSmtp = (string) $outgoing->smtp_mode === 'custom';

		return [
			'mailer' => $customSmtp ? 'smtp' : (string) $config->get('mailer', ''),
			'transport_source' => $customSmtp ? 'custom' : 'joomla',
			'smtp_host' => $customSmtp ? (string) $outgoing->smtp_host : (string) $config->get('smtphost', ''),
			'sender_email' => $sender['email'],
			'sender_valid' => filter_var($sender['email'], FILTER_VALIDATE_EMAIL) !== false,
			'queue_paused' => (int) $params->get('queue_paused', 0) === 1,
			'batch_size' => (int) $params->get('batch_size', 25),
			'max_attempts' => (int) $params->get('max_attempts', 3),
			'retry_minutes' => (int) $params->get('retry_minutes', 15),
			'imap_available' => function_exists('imap_open'),
		];
	}
}
