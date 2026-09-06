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
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Persistence boundary for subscriptions, suppressions and audit events.
 */
final class SubscriberRepository
{
	public const STATUS_PENDING = 0;
	public const STATUS_SUBSCRIBED = 1;
	public const STATUS_UNSUBSCRIBED = 2;

	/**
	 * @param DatabaseInterface $db Database connection.
	 */
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/**
	 * Finds a subscriber by primary key.
	 *
	 * @param int $id Subscriber ID.
	 *
	 * @return object|null Subscriber row or null.
	 */
	public function findById(int $id): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_subscribers'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/**
	 * Finds a subscriber by normalized address.
	 *
	 * @param string $email Email address.
	 *
	 * @return object|null Subscriber row or null.
	 */
	public function findByEmail(string $email): ?object
	{
		$normalized = Address::normalize($email);
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_subscribers'))
			->where($this->db->quoteName('email_normalized') . ' = :email')
			->bind(':email', $normalized);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}

	/**
	 * Finds a subscriber linked to a Joomla user.
	 *
	 * @param int $userId Joomla user ID.
	 *
	 * @return object|null Subscriber row or null.
	 */
	public function findByUserId(int $userId): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_subscribers'))
			->where($this->db->quoteName('user_id') . ' = :userId')
			->bind(':userId', $userId, ParameterType::INTEGER);

		return $this->db->setQuery($query)->loadObject() ?: null;
	}


	/**
	 * Reconciles a Joomla user's current email with the canonical subscriber row.
	 *
	 * Email addresses can change after a subscriber row was created. They can
	 * also collide with an existing external subscriber. This method resolves
	 * both cases without violating either unique key and without leaving the
	 * previous user address active as a second recipient.
	 *
	 * @param int    $userId Joomla user ID.
	 * @param string $email  Current Joomla user email.
	 *
	 * @return object|null Canonical subscriber row, or null if none exists yet.
	 */
	public function reconcileUserSubscriber(int $userId, string $email): ?object
	{
		$email = trim($email);

		if ($userId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return null;
		}

		$normalized = Address::normalize($email);
		$byUser = $this->findByUserId($userId);
		$byEmail = $this->findByEmail($email);

		if ($byUser === null && $byEmail === null)
		{
			return null;
		}

		$now = (new Date('now', 'UTC'))->toSql();

		if ($byUser === null)
		{
			$subscriberId = (int) $byEmail->id;
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__pungamail_subscribers'))
				->set($this->db->quoteName('user_id') . ' = :userId')
				->set($this->db->quoteName('email') . ' = :email')
				->set($this->db->quoteName('modified') . ' = :modified')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':userId', $userId, ParameterType::INTEGER)
				->bind(':email', $email)
				->bind(':modified', $now)
				->bind(':id', $subscriberId, ParameterType::INTEGER);
			$this->db->setQuery($query)->execute();
			$this->recordEvent($subscriberId, 'user_linked');

			return $this->findById($subscriberId);
		}

		if ($byEmail === null || (int) $byEmail->id === (int) $byUser->id)
		{
			$subscriberId = (int) $byUser->id;

			if ((string) $byUser->email !== $email || (string) $byUser->email_normalized !== $normalized)
			{
				$oldNormalized = (string) $byUser->email_normalized;
				$query = $this->db->getQuery(true)
					->update($this->db->quoteName('#__pungamail_subscribers'))
					->set($this->db->quoteName('email') . ' = :email')
					->set($this->db->quoteName('email_normalized') . ' = :normalized')
					->set($this->db->quoteName('modified') . ' = :modified')
					->where($this->db->quoteName('id') . ' = :id')
					->bind(':email', $email)
					->bind(':normalized', $normalized)
					->bind(':modified', $now)
					->bind(':id', $subscriberId, ParameterType::INTEGER);
				$this->db->setQuery($query)->execute();

				if ((int) $byUser->status === self::STATUS_UNSUBSCRIBED)
				{
					$this->suppress($subscriberId, $normalized, 'unsubscribed');
				}

				$this->recordEvent($subscriberId, 'user_email_changed', null, null, ['previous_email' => $oldNormalized]);
			}

			return $this->findById($subscriberId);
		}

		// A row linked to this Joomla user and another row for the user's new
		// address both exist. The row for the current address becomes canonical;
		// the previous user row is retired and suppressed in one transaction.
		$this->db->transactionStart();

		try
		{
			$oldId = (int) $byUser->id;
			$newId = (int) $byEmail->id;
			$unsubscribedStatus = self::STATUS_UNSUBSCRIBED;
			$oldNormalized = (string) $byUser->email_normalized;
			$retire = $this->db->getQuery(true)
				->update($this->db->quoteName('#__pungamail_subscribers'))
				->set($this->db->quoteName('user_id') . ' = NULL')
				->set($this->db->quoteName('status') . ' = :status')
				->set($this->db->quoteName('source') . ' = ' . $this->db->quote('user-previous'))
				->set($this->db->quoteName('confirmation_token_hash') . ' = NULL')
				->set($this->db->quoteName('confirmation_expires') . ' = NULL')
				->set($this->db->quoteName('unsubscribed_at') . ' = :unsubscribedAt')
				->set($this->db->quoteName('modified') . ' = :modified')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':status', $unsubscribedStatus, ParameterType::INTEGER)
				->bind(':unsubscribedAt', $now)
				->bind(':modified', $now)
				->bind(':id', $oldId, ParameterType::INTEGER);
			$this->db->setQuery($retire)->execute();
			$this->suppress($oldId, $oldNormalized, 'email-changed');

			$link = $this->db->getQuery(true)
				->update($this->db->quoteName('#__pungamail_subscribers'))
				->set($this->db->quoteName('user_id') . ' = :userId')
				->set($this->db->quoteName('email') . ' = :email')
				->set($this->db->quoteName('modified') . ' = :modified')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':userId', $userId, ParameterType::INTEGER)
				->bind(':email', $email)
				->bind(':modified', $now)
				->bind(':id', $newId, ParameterType::INTEGER);
			$this->db->setQuery($link)->execute();
			$this->recordEvent($oldId, 'user_email_replaced', null, null, ['replacement_subscriber_id' => $newId]);
			$this->recordEvent($newId, 'user_linked', null, null, ['previous_subscriber_id' => $oldId]);
			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}

		return $this->findById((int) $byEmail->id);
	}

	/**
	 * Returns the effective subscription state for a Joomla user.
	 *
	 * Users without an explicit row follow the component's configured default,
	 * unless their current address is present on the permanent suppression list.
	 *
	 * @param int    $userId User ID.
	 * @param string $email  Current user email.
	 *
	 * @return bool Effective subscription state.
	 */
	public function isUserSubscribed(int $userId, string $email): bool
	{
		$normalized = Address::normalize($email);
		$subscriber = $this->reconcileUserSubscriber($userId, $email);

		if ($this->isSuppressed($normalized))
		{
			return false;
		}

		if ($subscriber !== null)
		{
			return (int) $subscriber->status === self::STATUS_SUBSCRIBED;
		}

		return (bool) ComponentHelper::getParams('com_pungamail')->get('default_user_subscribed', 0);
	}

	/**
	 * Saves an explicit Joomla-user preference.
	 *
	 * @param int    $userId     Joomla user ID.
	 * @param string $email      User email.
	 * @param bool   $subscribed Requested state.
	 * @param string $recipientName Joomla display name, when known.
	 * @param string $eventSource Audit-event source prefix.
	 *
	 * @return int Subscriber ID.
	 */
	public function setUserPreference(int $userId, string $email, bool $subscribed, string $recipientName = '', string $eventSource = 'profile'): int
	{
		$normalized = Address::normalize($email);
		$recipientName = trim($recipientName);
		$eventSource = $eventSource === 'administrator' ? 'administrator' : 'profile';
		$now = (new Date('now', 'UTC'))->toSql();
		$subscriber = $this->reconcileUserSubscriber($userId, $email);
		$status = $subscribed ? self::STATUS_SUBSCRIBED : self::STATUS_UNSUBSCRIBED;

		if ($subscriber === null)
		{
			$row = (object) [
				'user_id' => $userId,
				'email' => trim($email),
				'recipient_name' => $recipientName,
				'email_normalized' => $normalized,
				'status' => $status,
				'source' => 'user',
				'language' => null,
				'confirmation_token_hash' => null,
				'confirmation_expires' => null,
				'confirmed_at' => $subscribed ? $now : null,
				'unsubscribed_at' => $subscribed ? null : $now,
				'created' => $now,
				'modified' => $now,
			];
			$this->db->insertObject('#__pungamail_subscribers', $row, 'id');
			$id = (int) $row->id;
		}
		else
		{
			$id = (int) $subscriber->id;
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__pungamail_subscribers'))
				->set($this->db->quoteName('user_id') . ' = :userId')
				->set($this->db->quoteName('email') . ' = :email')
				->set($this->db->quoteName('email_normalized') . ' = :normalized')
				->set($this->db->quoteName('recipient_name') . ' = :recipientName')
				->set($this->db->quoteName('status') . ' = :status')
				->set($this->db->quoteName('source') . ' = ' . $this->db->quote('user'))
				->set($this->db->quoteName('confirmation_token_hash') . ' = NULL')
				->set($this->db->quoteName('confirmation_expires') . ' = NULL')
				->set($this->db->quoteName('confirmed_at') . ' = ' . ($subscribed ? ':now' : $this->db->quoteName('confirmed_at')))
				->set($this->db->quoteName('unsubscribed_at') . ' = ' . ($subscribed ? 'NULL' : ':now2'))
				->set($this->db->quoteName('modified') . ' = :modified')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':userId', $userId, ParameterType::INTEGER)
				->bind(':email', $email)
				->bind(':normalized', $normalized)
				->bind(':recipientName', $recipientName)
				->bind(':status', $status, ParameterType::INTEGER)
				->bind(':modified', $now)
				->bind(':id', $id, ParameterType::INTEGER);

			if ($subscribed)
			{
				$query->bind(':now', $now);
			}
			else
			{
				$query->bind(':now2', $now);
			}

			$this->db->setQuery($query)->execute();
		}

		if ($subscribed)
		{
			$this->removeSuppression($normalized);
			$this->recordEvent($id, $eventSource . '_subscribed');
		}
		else
		{
			$this->suppress($id, $normalized, 'unsubscribed');
			$this->recordEvent($id, $eventSource . '_unsubscribed');
		}

		return $id;
	}


	/**
	 * Adds or reactivates an external subscriber from the administrator UI.
	 *
	 * This is an explicit privileged action and therefore creates an active
	 * subscription immediately instead of starting the public double-opt-in flow.
	 * Any existing suppression for the address is removed deliberately.
	 *
	 * @param string $email Email address to subscribe.
	 *
	 * @return int Subscriber ID.
	 */
	public function addAdministratorExternal(string $email): int
	{
		$email = trim($email);

		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException('Invalid email address.');
		}

		$normalized = Address::normalize($email);
		$now = (new Date('now', 'UTC'))->toSql();
		$subscriber = $this->findByEmail($email);

		if ($subscriber === null)
		{
			$row = (object) [
				'user_id' => null,
				'email' => $email,
				'recipient_name' => '',
				'email_normalized' => $normalized,
				'status' => self::STATUS_SUBSCRIBED,
				'source' => 'administrator',
				'language' => null,
				'confirmation_token_hash' => null,
				'confirmation_expires' => null,
				'confirmed_at' => $now,
				'unsubscribed_at' => null,
				'created' => $now,
				'modified' => $now,
			];
			$this->db->insertObject('#__pungamail_subscribers', $row, 'id');
			$id = (int) $row->id;
		}
		else
		{
			$id = (int) $subscriber->id;
			$status = self::STATUS_SUBSCRIBED;
			$source = $subscriber->user_id !== null ? 'user' : 'administrator';
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__pungamail_subscribers'))
				->set($this->db->quoteName('email') . ' = :email')
				->set($this->db->quoteName('email_normalized') . ' = :normalized')
				->set($this->db->quoteName('status') . ' = :status')
				->set($this->db->quoteName('source') . ' = :source')
				->set($this->db->quoteName('confirmation_token_hash') . ' = NULL')
				->set($this->db->quoteName('confirmation_expires') . ' = NULL')
				->set($this->db->quoteName('confirmed_at') . ' = :confirmedAt')
				->set($this->db->quoteName('unsubscribed_at') . ' = NULL')
				->set($this->db->quoteName('modified') . ' = :modified')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':email', $email)
				->bind(':normalized', $normalized)
				->bind(':status', $status, ParameterType::INTEGER)
				->bind(':source', $source)
				->bind(':confirmedAt', $now)
				->bind(':modified', $now)
				->bind(':id', $id, ParameterType::INTEGER);
			$this->db->setQuery($query)->execute();
		}

		$this->removeSuppression($normalized);
		$this->recordEvent($id, 'administrator_subscribed');

		return $id;
	}

	/**
	 * Creates or refreshes an external pending subscription.
	 *
	 * @param string $email       Requested address.
	 * @param string $tokenHash   SHA-256 confirmation token hash.
	 * @param string $expiresAt   UTC expiry SQL timestamp.
	 * @param string $language    Site language tag.
	 *
	 * @return int Subscriber ID.
	 */
	public function storePendingExternal(string $email, string $tokenHash, string $expiresAt, string $language): int
	{
		$normalized = Address::normalize($email);
		$now = (new Date('now', 'UTC'))->toSql();
		$subscriber = $this->findByEmail($email);

		if ($subscriber !== null && (int) $subscriber->status === self::STATUS_SUBSCRIBED && !$this->isSuppressed($normalized))
		{
			return (int) $subscriber->id;
		}

		if ($subscriber === null)
		{
			$row = (object) [
				'user_id' => null,
				'email' => trim($email),
				'email_normalized' => $normalized,
				'status' => self::STATUS_PENDING,
				'source' => 'module',
				'language' => $language,
				'confirmation_token_hash' => $tokenHash,
				'confirmation_expires' => $expiresAt,
				'confirmed_at' => null,
				'unsubscribed_at' => null,
				'created' => $now,
				'modified' => $now,
			];
			$this->db->insertObject('#__pungamail_subscribers', $row, 'id');

			return (int) $row->id;
		}

		$id = (int) $subscriber->id;
		$pendingStatus = self::STATUS_PENDING;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_subscribers'))
			->set($this->db->quoteName('email') . ' = :email')
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('source') . ' = ' . $this->db->quote('module'))
			->set($this->db->quoteName('language') . ' = :language')
			->set($this->db->quoteName('confirmation_token_hash') . ' = :token')
			->set($this->db->quoteName('confirmation_expires') . ' = :expires')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':email', $email)
			->bind(':status', $pendingStatus, ParameterType::INTEGER)
			->bind(':language', $language)
			->bind(':token', $tokenHash)
			->bind(':expires', $expiresAt)
			->bind(':modified', $now)
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();

		return $id;
	}

	/**
	 * Confirms an unexpired confirmation token.
	 *
	 * @param string $tokenHash SHA-256 token hash.
	 *
	 * @return object|null Confirmed subscriber or null on invalid/expired token.
	 */
	public function confirm(string $tokenHash): ?object
	{
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__pungamail_subscribers'))
			->where($this->db->quoteName('confirmation_token_hash') . ' = :token')
			->where($this->db->quoteName('confirmation_expires') . ' >= :now')
			->bind(':token', $tokenHash)
			->bind(':now', $now);
		$subscriber = $this->db->setQuery($query)->loadObject();

		if (!$subscriber)
		{
			return null;
		}

		$id = (int) $subscriber->id;
		$subscribedStatus = self::STATUS_SUBSCRIBED;
		$update = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_subscribers'))
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('confirmation_token_hash') . ' = NULL')
			->set($this->db->quoteName('confirmation_expires') . ' = NULL')
			->set($this->db->quoteName('confirmed_at') . ' = :now')
			->set($this->db->quoteName('unsubscribed_at') . ' = NULL')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':status', $subscribedStatus, ParameterType::INTEGER)
			->bind(':now', $now)
			->bind(':modified', $now)
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($update)->execute();

		$this->removeSuppression((string) $subscriber->email_normalized);
		$this->recordEvent($id, 'confirmed');

		return $this->findById($id);
	}

	/**
	 * Unsubscribes and permanently suppresses an address.
	 *
	 * @param int    $subscriberId Subscriber ID.
	 * @param string $reason       Suppression reason.
	 *
	 * @return bool True if a subscriber existed.
	 */
	public function unsubscribe(int $subscriberId, string $reason = 'unsubscribed'): bool
	{
		$subscriber = $this->findById($subscriberId);

		if ($subscriber === null)
		{
			return false;
		}

		$now = (new Date('now', 'UTC'))->toSql();
		$unsubscribedStatus = self::STATUS_UNSUBSCRIBED;
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_subscribers'))
			->set($this->db->quoteName('status') . ' = :status')
			->set($this->db->quoteName('confirmation_token_hash') . ' = NULL')
			->set($this->db->quoteName('confirmation_expires') . ' = NULL')
			->set($this->db->quoteName('unsubscribed_at') . ' = :now')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':status', $unsubscribedStatus, ParameterType::INTEGER)
			->bind(':now', $now)
			->bind(':modified', $now)
			->bind(':id', $subscriberId, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
		$this->suppress($subscriberId, (string) $subscriber->email_normalized, $reason);
		$this->recordEvent($subscriberId, 'unsubscribed', null, null, ['reason' => $reason]);

		return true;
	}

	/**
	 * Tests whether an address is permanently suppressed.
	 *
	 * @param string $normalizedEmail Normalized address.
	 *
	 * @return bool True when suppressed.
	 */
	public function isSuppressed(string $normalizedEmail): bool
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__pungamail_suppressions'))
			->where($this->db->quoteName('email_normalized') . ' = :email')
			->bind(':email', $normalizedEmail);

		return (int) $this->db->setQuery($query)->loadResult() > 0;
	}

	/**
	 * Returns whether a signup IP has exceeded its hourly request limit.
	 *
	 * @param string $ipAddress Remote address.
	 * @param int    $limit     Maximum requests per hour.
	 *
	 * @return bool True when the request should be rejected/throttled.
	 */
	public function isIpRateLimited(string $ipAddress, int $limit): bool
	{
		if ($ipAddress === '')
		{
			return false;
		}

		$since = (new Date('-1 hour', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__pungamail_events'))
			->where($this->db->quoteName('event_type') . ' = ' . $this->db->quote('signup_requested'))
			->where($this->db->quoteName('ip_address') . ' = :ip')
			->where($this->db->quoteName('created') . ' >= :since')
			->bind(':ip', $ipAddress)
			->bind(':since', $since);

		return (int) $this->db->setQuery($query)->loadResult() >= $limit;
	}

	/**
	 * Determines whether another confirmation email may be sent for an address.
	 *
	 * @param string $email   Email address.
	 * @param int    $minutes Minimum resend interval.
	 *
	 * @return bool True when a new confirmation may be sent.
	 */
	public function mayResendConfirmation(string $email, int $minutes): bool
	{
		$subscriber = $this->findByEmail($email);

		if ($subscriber === null)
		{
			return true;
		}

		$threshold = (new Date('-' . max(1, $minutes) . ' minutes', 'UTC'))->toSql();

		return (string) $subscriber->modified <= $threshold;
	}

	/**
	 * Ensures a recipient has a canonical subscriber row.
	 *
	 * @param int|null $userId Joomla user ID, if applicable.
	 * @param string   $email  Recipient email.
	 *
	 * @return object Subscriber row.
	 */
	public function ensureRecipient(?int $userId, string $email): object
	{
		$existing = $userId !== null ? $this->reconcileUserSubscriber($userId, $email) : null;
		$existing ??= $this->findByEmail($email);

		if ($existing !== null)
		{
			return $existing;
		}

		$now = (new Date('now', 'UTC'))->toSql();
		$row = (object) [
			'user_id' => $userId,
			'email' => trim($email),
			'email_normalized' => Address::normalize($email),
			'status' => self::STATUS_SUBSCRIBED,
			'source' => $userId !== null ? 'user-group' : 'administrator',
			'language' => null,
			'confirmation_token_hash' => null,
			'confirmation_expires' => null,
			'confirmed_at' => $now,
			'unsubscribed_at' => null,
			'created' => $now,
			'modified' => $now,
		];
		$this->db->insertObject('#__pungamail_subscribers', $row, 'id');

		return $row;
	}

	/**
	 * Records a subscriber audit event.
	 *
	 * @param int|null   $subscriberId Subscriber ID.
	 * @param string     $eventType    Event identifier.
	 * @param string|null $ipAddress    Remote IP.
	 * @param string|null $userAgent    Remote user agent.
	 * @param array      $metadata      Optional structured metadata.
	 *
	 * @return void
	 */
	public function recordEvent(?int $subscriberId, string $eventType, ?string $ipAddress = null, ?string $userAgent = null, array $metadata = []): void
	{
		$row = (object) [
			'subscriber_id' => $subscriberId,
			'event_type' => $eventType,
			'newsletter_id' => isset($metadata['newsletter_id']) ? (int) $metadata['newsletter_id'] : null,
			'ip_address' => $ipAddress !== '' ? $ipAddress : null,
			'user_agent' => $userAgent !== '' ? mb_substr((string) $userAgent, 0, 512) : null,
			'metadata' => $metadata !== [] ? json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
			'created' => (new Date('now', 'UTC'))->toSql(),
		];
		$this->db->insertObject('#__pungamail_events', $row, 'id');
	}

	/**
	 * Inserts or refreshes a suppression record.
	 *
	 * @param int|null $subscriberId   Subscriber ID.
	 * @param string   $normalizedEmail Normalized address.
	 * @param string   $reason          Suppression reason.
	 *
	 * @return void
	 */
	public function suppress(?int $subscriberId, string $normalizedEmail, string $reason): void
	{
		$this->removeSuppression($normalizedEmail);
		$row = (object) [
			'subscriber_id' => $subscriberId,
			'email_normalized' => $normalizedEmail,
			'reason' => $reason,
			'created' => (new Date('now', 'UTC'))->toSql(),
		];
		$this->db->insertObject('#__pungamail_suppressions', $row, 'id');
	}

	/**
	 * Removes a suppression after an explicit opt-in.
	 *
	 * @param string $normalizedEmail Normalized address.
	 *
	 * @return void
	 */
	public function removeSuppression(string $normalizedEmail): void
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__pungamail_suppressions'))
			->where($this->db->quoteName('email_normalized') . ' = :email')
			->bind(':email', $normalizedEmail);
		$this->db->setQuery($query)->execute();
	}

	/**
	 * Returns the active delivery-block reason for an address.
	 *
	 * @param string $normalizedEmail Normalized address.
	 *
	 * @return string|null Suppression reason, or null when delivery is not blocked.
	 */
	public function getSuppressionReason(string $normalizedEmail): ?string
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('reason'))
			->from($this->db->quoteName('#__pungamail_suppressions'))
			->where($this->db->quoteName('email_normalized') . ' = :email')
			->bind(':email', $normalizedEmail);
		$reason = trim((string) $this->db->setQuery($query)->loadResult());

		return $reason !== '' ? $reason : null;
	}

	/**
	 * Clears bounce-origin suppression without reactivating a global unsubscribe.
	 * Bounce history is retained; only counters and the current bounce barrier reset.
	 *
	 * @return bool True when a bounce suppression was cleared.
	 */
	public function clearBounceSuppression(int $subscriberId): bool
	{
		$subscriber = $this->findById($subscriberId);

		if ($subscriber === null)
		{
			return false;
		}

		$normalized = (string) $subscriber->email_normalized;
		$reason = $this->getSuppressionReason($normalized);

		if (!in_array($reason, ['hard-bounce', 'soft-bounce-threshold'], true))
		{
			return false;
		}

		$this->removeSuppression($normalized);
		$now = (new Date('now', 'UTC'))->toSql();
		$update = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_subscribers'))
			->set($this->db->quoteName('soft_bounce_count') . ' = 0')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':modified', $now)
			->bind(':id', $subscriberId, ParameterType::INTEGER);
		$this->db->setQuery($update)->execute();
		$this->recordEvent($subscriberId, 'bounce_suppression_cleared');

		return true;
	}

	/**
	 * Removes the live subscriber record and mutable membership/request data.
	 *
	 * Suppression and historical delivery/bounce/event rows are deliberately kept.
	 * Their subscriber reference is detached where nullable so deleting an obsolete
	 * administration row does not erase audit history or revive a blocked address.
	 *
	 * @param int $subscriberId Subscriber ID.
	 *
	 * @return bool True when a subscriber was removed.
	 */
	public function deleteSubscriber(int $subscriberId): bool
	{
		$subscriber = $this->findById($subscriberId);

		if ($subscriber === null)
		{
			return false;
		}

		$this->db->transactionStart();

		try
		{
			$requestQuery = $this->db->getQuery(true)
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__pungamail_preference_requests'))
				->where($this->db->quoteName('subscriber_id') . ' = :subscriberId')
				->bind(':subscriberId', $subscriberId, ParameterType::INTEGER);
			$requestIds = array_map('intval', $this->db->setQuery($requestQuery)->loadColumn());

			if ($requestIds !== [])
			{
				$deleteRequestTopics = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__pungamail_preference_request_topics'))
					->whereIn($this->db->quoteName('request_id'), $requestIds);
				$this->db->setQuery($deleteRequestTopics)->execute();
			}

			foreach (['#__pungamail_subscriber_topics', '#__pungamail_preference_requests'] as $table)
			{
				$delete = $this->db->getQuery(true)
					->delete($this->db->quoteName($table))
					->where($this->db->quoteName('subscriber_id') . ' = :subscriberId')
					->bind(':subscriberId', $subscriberId, ParameterType::INTEGER);
				$this->db->setQuery($delete)->execute();
			}

			foreach (['#__pungamail_suppressions', '#__pungamail_bounces', '#__pungamail_events'] as $table)
			{
				$detach = $this->db->getQuery(true)
					->update($this->db->quoteName($table))
					->set($this->db->quoteName('subscriber_id') . ' = NULL')
					->where($this->db->quoteName('subscriber_id') . ' = :subscriberId')
					->bind(':subscriberId', $subscriberId, ParameterType::INTEGER);
				$this->db->setQuery($detach)->execute();
			}

			$deleteSubscriber = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__pungamail_subscribers'))
				->where($this->db->quoteName('id') . ' = :subscriberId')
				->bind(':subscriberId', $subscriberId, ParameterType::INTEGER);
			$this->db->setQuery($deleteSubscriber)->execute();
			$deleted = $this->db->getAffectedRows() === 1;

			$this->db->transactionCommit();

			return $deleted;
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();
			throw $e;
		}
	}

	/** Updates the optional recipient display name without changing consent state. */
	public function updateRecipientName(int $subscriberId, string $name): void
	{
		$name = trim($name);
		$now = (new Date('now', 'UTC'))->toSql();
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__pungamail_subscribers'))
			->set($this->db->quoteName('recipient_name') . ' = :recipientName')
			->set($this->db->quoteName('modified') . ' = :modified')
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':recipientName', $name)
			->bind(':modified', $now)
			->bind(':id', $subscriberId, ParameterType::INTEGER);
		$this->db->setQuery($query)->execute();
	}
}
