<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Retrieves and records standard delivery-status notifications over PHP IMAP. */
final class BounceService
{
	/**
	 * @param DatabaseInterface      $db          Database connection.
	 * @param MailSettingsRepository $settings    Mailbox settings.
	 * @param SubscriberRepository   $subscribers Subscriber repository.
	 */
	public function __construct(
		private readonly DatabaseInterface $db,
		private readonly MailSettingsRepository $settings,
		private readonly SubscriberRepository $subscribers,
		private readonly NewsletterRepository $newsletters
	)
	{
	}

	/** @return array{ok:bool,message:string} */
	public function testConnection(?array $override = null, string $newPassword = ''): array
	{
		$this->requireImap();
		$config = $override !== null ? (object) $override : $this->settings->getConnection();

		if ($newPassword === '' && !isset($config->bounce_password))
		{
			$stored = $this->settings->getConnection();
			$newPassword = (string) ($stored->bounce_password ?? '');
		}

		$password = $newPassword !== '' ? $newPassword : (string) ($config->bounce_password ?? '');
		$mailbox = $this->mailboxString($config);
		$connection = @imap_open($mailbox, (string) ($config->bounce_username ?? ''), $password, OP_READONLY, 1);

		if ($connection === false)
		{
			$message = imap_last_error() ?: Text::_('COM_PUNGAMAIL_BOUNCE_CONNECTION_FAILED');
			return ['ok' => false, 'message' => $message];
		}

		$count = imap_num_msg($connection);
		imap_close($connection);

		return ['ok' => true, 'message' => Text::sprintf('COM_PUNGAMAIL_BOUNCE_CONNECTION_OK', $count)];
	}

	/** @return array{processed:int,hard:int,soft:int,unknown:int,duplicates:int} */
	public function process(int $limit = 100): array
	{
		$this->requireImap();
		$result = ['processed' => 0, 'hard' => 0, 'soft' => 0, 'unknown' => 0, 'duplicates' => 0];

		if (!$this->acquireProcessLock())
		{
			return $result;
		}

		try
		{
			$config = $this->settings->getConnection();
			$mailbox = $this->mailboxString($config);
			$connection = @imap_open($mailbox, (string) $config->bounce_username, (string) $config->bounce_password, 0, 1);

			if ($connection === false)
			{
				throw new \RuntimeException(imap_last_error() ?: Text::_('COM_PUNGAMAIL_BOUNCE_CONNECTION_FAILED'));
			}

			$messages = imap_search($connection, 'UNSEEN') ?: [];
			$messages = array_slice($messages, 0, max(1, min(500, $limit)));

			try
			{
				foreach ($messages as $messageNumber)
				{
					$header = (string) imap_fetchheader($connection, $messageNumber, FT_PEEK);
					$body = (string) imap_body($connection, $messageNumber, FT_PEEK);
					$parsed = $this->parse($header, $body, (int) $messageNumber);

					if ($parsed === null)
					{
						imap_setflag_full($connection, (string) $messageNumber, '\\Seen');
						continue;
					}

					if ($this->record($parsed))
					{
						$result['processed']++;
						$result[$parsed['classification']]++;
					}
					else
					{
						$result['duplicates']++;
					}

					imap_setflag_full($connection, (string) $messageNumber, '\\Seen');
				}
			}
			finally
			{
				imap_close($connection);
			}
		}
		finally
		{
			$this->releaseProcessLock();
		}

		return $result;
	}

	/** @return bool */
	private function acquireProcessLock(): bool
	{
		$name = 'pungamail.bounces';
		$query = $this->db->getQuery(true)
			->select('GET_LOCK(:lockName, 0)')
			->bind(':lockName', $name);

		return (int) $this->db->setQuery($query)->loadResult() === 1;
	}

	/** @return void */
	private function releaseProcessLock(): void
	{
		$name = 'pungamail.bounces';
		$query = $this->db->getQuery(true)
			->select('RELEASE_LOCK(:lockName)')
			->bind(':lockName', $name);
		$this->db->setQuery($query)->loadResult();
	}

	/** @return array<string,mixed>|null */
	private function parse(string $header, string $body, int $messageNumber): ?array
	{
		$raw = $header . "\n" . $body;
		$email = '';

		foreach ([
			'/^(?:Final|Original)-Recipient:\s*(?:rfc822;)?\s*<?([^>\s;]+@[^>\s;]+)>?/mi',
			'/^X-Failed-Recipients:\s*<?([^>\s,]+@[^>\s,]+)>?/mi',
		] as $pattern)
		{
			if (preg_match($pattern, $raw, $match) === 1 && filter_var($match[1], FILTER_VALIDATE_EMAIL))
			{
				$email = (string) $match[1];
				break;
			}
		}

		$queueId = null;
		$newsletterId = null;

		if (preg_match('/^X-PungaMail-Queue-ID:\s*(\d+)/mi', $raw, $match) === 1
			|| preg_match('/pungamail-q(\d+)@/i', $raw, $match) === 1)
		{
			$queueId = (int) $match[1];
		}

		if (preg_match('/^X-PungaMail-Newsletter-ID:\s*(\d+)/mi', $raw, $match) === 1)
		{
			$newsletterId = (int) $match[1];
		}

		if ($queueId !== null)
		{
			$queue = $this->findQueue($queueId);

			if ($queue !== null)
			{
				$email = (string) $queue->email;
				$newsletterId = (int) $queue->newsletter_id;
			}
		}

		if ($email === '')
		{
			return null;
		}

		$statusCode = null;

		if (preg_match('/^Status:\s*([245]\.\d{1,3}\.\d{1,3})/mi', $raw, $match) === 1
			|| preg_match('/\b([45]\d\d)\b/', $raw, $match) === 1)
		{
			$statusCode = (string) $match[1];
		}

		$diagnostic = '';

		if (preg_match('/^Diagnostic-Code:\s*(?:[^;]+;)?\s*(.+)$/mi', $raw, $match) === 1)
		{
			$diagnostic = trim((string) $match[1]);
		}

		if ($diagnostic === '' && preg_match('/^Subject:\s*(.+)$/mi', $header, $match) === 1)
		{
			$diagnostic = trim((string) $match[1]);
		}

		$classification = $this->classify($statusCode, $diagnostic . ' ' . $body);
		$messageId = preg_match('/^Message-ID:\s*<?([^>\r\n]+)>?/mi', $header, $match) === 1 ? trim((string) $match[1]) : '';
		$date = preg_match('/^Date:\s*(.+)$/mi', $header, $match) === 1 ? strtotime((string) $match[1]) : false;
		$occurredAt = $date !== false ? gmdate('Y-m-d H:i:s', $date) : (new Date('now', 'UTC'))->toSql();

		return [
			'email' => $email,
			'email_normalized' => Address::normalize($email),
			'queue_id' => $queueId,
			'newsletter_id' => $newsletterId,
			'message_key' => hash('sha256', $messageId . '|' . $messageNumber . '|' . hash('sha256', $raw)),
			'classification' => $classification,
			'status_code' => $statusCode,
			'diagnostic' => mb_substr(preg_replace('/\s+/u', ' ', $diagnostic) ?? $diagnostic, 0, 2000, 'UTF-8'),
			'occurred_at' => $occurredAt,
		];
	}

	/** @return string */
	private function classify(?string $statusCode, string $diagnostic): string
	{
		$diagnostic = strtolower($diagnostic);

		if (preg_match('/mailbox.*full|over quota|quota exceeded|temporar|try again|greylist|timeout|unavailable/', $diagnostic) === 1)
		{
			return 'soft';
		}

		if ($statusCode !== null)
		{
			if (str_starts_with($statusCode, '5'))
			{
				return 'hard';
			}

			if (str_starts_with($statusCode, '4'))
			{
				return 'soft';
			}
		}

		if (preg_match('/user unknown|no such user|mailbox.*not found|recipient.*rejected|address.*invalid|does not exist/', $diagnostic) === 1)
		{
			return 'hard';
		}

		return 'unknown';
	}

	/** @param array<string,mixed> $bounce @return bool */
	private function record(array $bounce): bool
	{
		$messageKey = (string) $bounce['message_key'];
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__pungamail_bounces'))
			->where($this->db->quoteName('message_key') . ' = :messageKey')
			->bind(':messageKey', $messageKey);

		if ((int) $this->db->setQuery($query)->loadResult() > 0)
		{
			return false;
		}

		$subscriber = $this->subscribers->findByEmail((string) $bounce['email']);
		$queue = $bounce['queue_id'] !== null ? $this->findQueue((int) $bounce['queue_id']) : null;

		if ($queue === null)
		{
			$queue = $this->findRecentQueue((string) $bounce['email_normalized']);
		}

		$subscriberId = $subscriber !== null ? (int) $subscriber->id : ($queue !== null ? (int) $queue->subscriber_id : null);
		$newsletterId = $bounce['newsletter_id'] !== null ? (int) $bounce['newsletter_id'] : ($queue !== null ? (int) $queue->newsletter_id : null);
		$queueId = $queue !== null ? (int) $queue->id : null;
		$now = (new Date('now', 'UTC'))->toSql();
		$row = (object) [
			'subscriber_id' => $subscriberId,
			'newsletter_id' => $newsletterId,
			'queue_id' => $queueId,
			'email' => (string) $bounce['email'],
			'email_normalized' => (string) $bounce['email_normalized'],
			'message_key' => $messageKey,
			'classification' => (string) $bounce['classification'],
			'status_code' => $bounce['status_code'],
			'diagnostic' => (string) $bounce['diagnostic'] !== '' ? (string) $bounce['diagnostic'] : null,
			'occurred_at' => (string) $bounce['occurred_at'],
			'processed_at' => $now,
		];
		$this->db->transactionStart();

		try
		{
			$this->db->insertObject('#__pungamail_bounces', $row, 'id');

			if ($subscriberId !== null)
			{
				$this->updateSubscriberBounce($subscriberId, $bounce, $now);
			}

			if ($queueId !== null)
			{
				$bounceStatus = 'bounced';
				$bounceId = (int) $row->id;
				$update = $this->db->getQuery(true)
					->update($this->db->quoteName('#__pungamail_send_queue'))
					->set($this->db->quoteName('status') . ' = :status')
					->set($this->db->quoteName('bounce_id') . ' = :bounceId')
					->set($this->db->quoteName('modified') . ' = :modified')
					->where($this->db->quoteName('id') . ' = :id')
					->bind(':status', $bounceStatus)
					->bind(':bounceId', $bounceId, ParameterType::INTEGER)
					->bind(':modified', $now)
					->bind(':id', $queueId, ParameterType::INTEGER);
				$this->db->setQuery($update)->execute();
			}

			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}

		if ($newsletterId !== null)
		{
			$this->newsletters->refreshQueueCounters($newsletterId);
		}

		return true;
	}

	/** @param array<string,mixed> $bounce @return void */
	private function updateSubscriberBounce(int $subscriberId, array $bounce, string $now): void
	{
		$classification = (string) $bounce['classification'];
		$reason = trim((string) ($bounce['diagnostic'] ?? ''));
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_subscribers'))
			->set($this->db->quoteName('bounce_count') . ' = ' . $this->db->quoteName('bounce_count') . ' + 1')
			->set($this->db->quoteName('soft_bounce_count') . ($classification === 'soft' ? ' = ' . $this->db->quoteName('soft_bounce_count') . ' + 1' : ' = ' . $this->db->quoteName('soft_bounce_count')))
			->set($this->db->quoteName('last_bounce_at') . ' = :lastBounceAt')
			->set($this->db->quoteName('last_bounce_class') . ' = :classification')
			->set($this->db->quoteName('last_bounce_reason') . ' = :reason')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':lastBounceAt', $now)
			->bind(':classification', $classification)
			->bind(':reason', $reason)
			->bind(':modified', $now)
			->bind(':id', $subscriberId, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
		$subscriber = $this->subscribers->findById($subscriberId);

		if ($subscriber === null)
		{
			return;
		}

		if ($classification === 'hard')
		{
			$this->subscribers->suppress($subscriberId, (string) $subscriber->email_normalized, 'hard-bounce');
		}
		elseif ($classification === 'soft')
		{
			$threshold = max(1, (int) ComponentHelper::getParams('com_pungamail')->get('soft_bounce_threshold', 3));

			if ((int) $subscriber->soft_bounce_count >= $threshold)
			{
				$this->subscribers->suppress($subscriberId, (string) $subscriber->email_normalized, 'soft-bounce-threshold');
			}
		}
	}

	/** @return object|null */
	private function findQueue(int $id): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/** @return object|null */
	private function findRecentQueue(string $normalized): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_send_queue'))
			->where($this->db->quoteName('email_normalized') . ' = :email')
			->where($this->db->quoteName('sent_at') . ' IS NOT NULL')
			->order($this->db->quoteName('sent_at') . ' DESC')
			->bind(':email', $normalized);

		return $this->db->setQuery($query, 0, 1)->loadObject() ?: null;
	}

	/** @return string */
	private function mailboxString(object $config): string
	{
		$host = trim((string) ($config->bounce_host ?? ''));
		$port = max(1, (int) ($config->bounce_port ?? 993));
		$security = (string) ($config->bounce_security ?? 'ssl');
		$mailbox = trim((string) ($config->bounce_mailbox ?? 'INBOX')) ?: 'INBOX';
		$options = '/imap';

		if (in_array($security, ['ssl', 'tls'], true))
		{
			$options .= '/' . $security;
		}

		if ((int) ($config->validate_cert ?? 1) !== 1)
		{
			$options .= '/novalidate-cert';
		}

		if ($host === '')
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_BOUNCE_CONFIGURATION_INCOMPLETE'));
		}

		return '{' . $host . ':' . $port . $options . '}' . $mailbox;
	}

	/** @return void */
	private function requireImap(): void
	{
		if (!function_exists('imap_open'))
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_BOUNCE_IMAP_MISSING'));
		}
	}
}
