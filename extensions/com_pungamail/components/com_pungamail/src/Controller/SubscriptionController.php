<?php
/**
 * @package     Punga.Mail
 * @subpackage  Site.Controller
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Site\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;
use Punga\Component\PungaMail\Administrator\Service\Address;
use Punga\Component\PungaMail\Administrator\Service\ErrorMessage;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;
use Punga\Component\PungaMail\Administrator\Service\SubscriberRepository;

/**
 * Public subscription, confirmation and unsubscribe actions.
 */
final class SubscriptionController extends BaseController
{
	/**
	 * Accepts a public signup request and sends a double-opt-in email.
	 *
	 * The response is intentionally generic so the endpoint cannot be used to
	 * enumerate subscribed email addresses.
	 *
	 * @return void
	 */
	public function request(): void
	{
		$this->requireFormToken();
		$app = Factory::getApplication();
		$input = $app->getInput();
		$email = trim($input->post->getString('email'));
		$availableTopics = $this->moduleTopics((int) $input->post->getInt('module_id', 0));
		$visibleTopicIds = array_map(static fn (object $topic): int => (int) $topic->id, $availableTopics);
		$selectedTopicIds = array_values(array_intersect($visibleTopicIds, array_map('intval', (array) $input->post->get('topic_ids', [], 'array'))));
		$honeypot = trim($input->post->getString('website'));
		$redirect = Route::_('index.php?option=com_pungamail&view=message&type=requested', false);

		if ($honeypot !== '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$this->setRedirect($redirect);
			return;
		}

		$params = ComponentHelper::getParams('com_pungamail');
		$repo = ServiceFactory::subscribers();
		$ip = $input->server->getString('REMOTE_ADDR');
		$userAgent = $input->server->getString('HTTP_USER_AGENT');
		$hourlyLimit = max(1, (int) $params->get('signup_ip_hourly_limit', 12));

		if ($repo->isIpRateLimited($ip, $hourlyLimit))
		{
			$this->setRedirect($redirect);
			return;
		}

		$existing = $repo->findByEmail($email);
		$repo->recordEvent($existing?->id ? (int) $existing->id : null, 'signup_requested', $ip, $userAgent);

		if ($existing !== null
			&& (int) $existing->status === SubscriberRepository::STATUS_SUBSCRIBED
			&& !$repo->isSuppressed(Address::normalize($email)))
		{
			if ($visibleTopicIds !== [])
			{
				$resendMinutes = max(1, (int) $params->get('resend_minutes', 10));

				if (!$repo->mayResendConfirmation($email, $resendMinutes))
				{
					$this->setRedirect($redirect);
					return;
				}

				$tokenData = ServiceFactory::tokens()->createConfirmationToken();
				$hours = max(1, (int) $params->get('confirmation_hours', 48));
				$expires = (new Date('+' . $hours . ' hours', 'UTC'))->toSql();
				ServiceFactory::topics()->createPreferenceRequest((int) $existing->id, $visibleTopicIds, $selectedTopicIds, $tokenData['hash'], $expires);

				try
				{
					ServiceFactory::mail()->sendPreferenceConfirmation($email, $tokenData['token']);
					$repo->recordEvent((int) $existing->id, 'topic_confirmation_sent', $ip, $userAgent);
				}
				catch (\Throwable $e)
				{
					$repo->recordEvent((int) $existing->id, 'topic_confirmation_send_failed', $ip, $userAgent, ['error' => ErrorMessage::sanitize($e, 500)]);
				}
			}

			$this->setRedirect($redirect);
			return;
		}

		$resendMinutes = max(1, (int) $params->get('resend_minutes', 10));

		if (!$repo->mayResendConfirmation($email, $resendMinutes))
		{
			$this->setRedirect($redirect);
			return;
		}

		$tokenData = ServiceFactory::tokens()->createConfirmationToken();
		$hours = max(1, (int) $params->get('confirmation_hours', 48));
		$expires = (new Date('+' . $hours . ' hours', 'UTC'))->toSql();
		$language = $app->getLanguage()->getTag();
		$subscriberId = $repo->storePendingExternal($email, $tokenData['hash'], $expires, $language);
		ServiceFactory::topics()->stageInitialTopics($subscriberId, $visibleTopicIds, $selectedTopicIds);

		try
		{
			ServiceFactory::mail()->sendConfirmation($email, $tokenData['token']);
			$repo->recordEvent($subscriberId, 'confirmation_sent', $ip, $userAgent);
		}
		catch (\Throwable $e)
		{
			$repo->recordEvent($subscriberId, 'confirmation_send_failed', $ip, $userAgent, ['error' => ErrorMessage::sanitize($e, 500)]);
		}

		$this->setRedirect($redirect);
	}

	/**
	 * Confirms a double-opt-in request after an explicit POST from the form.
	 *
	 * @return void
	 */
	public function confirm(): void
	{
		$this->requireFormToken();
		$app = Factory::getApplication();
		$token = trim($app->getInput()->post->getString('token'));
		$hash = ServiceFactory::tokens()->hashConfirmationToken($token);

		if ($app->getInput()->post->getCmd('kind') === 'topics')
		{
			if (!ServiceFactory::topics()->confirmPreferenceRequest($hash))
			{
				$this->setRedirect(Route::_('index.php?option=com_pungamail&view=message&type=invalid_confirmation', false));
				return;
			}

			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=message&type=preferences_updated', false));
			return;
		}

		$subscriber = ServiceFactory::subscribers()->confirm($hash);

		if ($subscriber === null)
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=message&type=invalid_confirmation', false));
			return;
		}

		ServiceFactory::topics()->activatePendingTopics((int) $subscriber->id);

		ServiceFactory::subscribers()->recordEvent(
			(int) $subscriber->id,
			'confirmation_completed',
			$app->getInput()->server->getString('REMOTE_ADDR'),
			$app->getInput()->server->getString('HTTP_USER_AGENT')
		);
		$this->setRedirect(Route::_('index.php?option=com_pungamail&view=message&type=confirmed', false));
	}

	/**
	 * Handles the explicit visible unsubscribe form POST.
	 *
	 * @return void
	 */
	public function unsubscribe(): void
	{
		$this->requireFormToken();
		$app = Factory::getApplication();
		$input = $app->getInput();
		$id = $input->post->getInt('id');
		$token = $input->post->getString('token');
		$repo = ServiceFactory::subscribers();
		$subscriber = $repo->findById($id);

		if ($subscriber === null || !ServiceFactory::tokens()->validateUnsubscribeToken($id, $token))
		{
			$this->setRedirect(Route::_('index.php?option=com_pungamail&view=message&type=invalid_unsubscribe', false));
			return;
		}

		$repo->unsubscribe($id, 'unsubscribed');
		$newsletterId = $input->post->getInt('mid');
		$repo->recordEvent($id, 'unsubscribe_completed', $input->server->getString('REMOTE_ADDR'), $input->server->getString('HTTP_USER_AGENT'), ['newsletter_id' => $newsletterId]);
		$this->setRedirect(Route::_('index.php?option=com_pungamail&view=message&type=unsubscribed', false));
	}

	/**
	 * Updates the newsletter preference of the currently authenticated user.
	 *
	 * @return void
	 */
	public function userPreference(): void
	{
		$this->requireFormToken();
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		if ($user->guest || (int) $user->id <= 0 || !filter_var((string) $user->email, FILTER_VALIDATE_EMAIL))
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_AUTHENTICATION_REQUIRED'), 403);
		}

		$subscribed = $app->getInput()->post->getInt('subscribed') === 1;
		$repo = ServiceFactory::subscribers();
		$repo->setUserPreference((int) $user->id, (string) $user->email, $subscribed);
		$subscriber = $repo->findByUserId((int) $user->id);
		$repo->recordEvent(
			$subscriber !== null ? (int) $subscriber->id : null,
			$subscribed ? 'user_subscribed' : 'user_unsubscribed',
			$app->getInput()->server->getString('REMOTE_ADDR'),
			$app->getInput()->server->getString('HTTP_USER_AGENT')
		);

		$this->setRedirect($this->safeReturn(), Text::_($subscribed ? 'COM_PUNGAMAIL_PREFERENCE_ENABLED' : 'COM_PUNGAMAIL_PREFERENCE_DISABLED'));
	}

	/** Updates only the topics exposed by the current module or menu page. */
	public function userTopics(): void
	{
		$this->requireFormToken();
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		if ($user->guest || (int) $user->id <= 0 || !filter_var((string) $user->email, FILTER_VALIDATE_EMAIL))
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_AUTHENTICATION_REQUIRED'), 403);
		}

		$topics = $this->moduleTopics((int) $app->getInput()->post->getInt('module_id', 0));
		$visibleIds = array_map(static fn (object $topic): int => (int) $topic->id, $topics);
		$selectedIds = array_values(array_intersect($visibleIds, array_map('intval', (array) $app->getInput()->post->get('topic_ids', [], 'array'))));
		$repo = ServiceFactory::subscribers();
		$subscriber = $repo->findByUserId((int) $user->id) ?? $repo->findByEmail((string) $user->email);

		if ($subscriber === null && $selectedIds === [])
		{
			$this->setRedirect($this->safeReturn(), Text::_('COM_PUNGAMAIL_TOPIC_PREFERENCES_SAVED'));
			return;
		}

		if ($subscriber === null)
		{
			// Topic choices and the global newsletter preference are independent.
			// Create a canonical row using the user's current effective preference,
			// but never reactivate an opted-out or suppressed address here.
			$subscriberId = $repo->setUserPreference(
				(int) $user->id,
				(string) $user->email,
				$repo->isUserSubscribed((int) $user->id, (string) $user->email),
				(string) $user->name
			);
		}
		else
		{
			$subscriberId = (int) $subscriber->id;
		}

		ServiceFactory::topics()->updateVisibleTopics($subscriberId, $visibleIds, $selectedIds);
		$repo->recordEvent($subscriberId, 'topic_preferences_updated', $app->getInput()->server->getString('REMOTE_ADDR'), $app->getInput()->server->getString('HTTP_USER_AGENT'));
		$this->setRedirect($this->safeReturn(), Text::_('COM_PUNGAMAIL_TOPIC_PREFERENCES_SAVED'));
	}

	/**
	 * Implements the RFC 8058 one-click unsubscribe POST endpoint.
	 *
	 * This endpoint intentionally does not use a Joomla session CSRF token:
	 * mailbox providers invoke it server-to-server. Authenticity is established
	 * by the signed recipient token and the required standards-defined POST body.
	 *
	 * @return void
	 */
	public function oneClickUnsubscribe(): void
	{
		$app = Factory::getApplication();
		$input = $app->getInput();
		$method = strtoupper($input->server->getString('REQUEST_METHOD'));
		$postMarker = $input->post->getString('List-Unsubscribe');

		if ($method !== 'POST' || $postMarker !== 'One-Click')
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_ONE_CLICK_REQUEST'), 400);
		}

		$id = $input->getInt('id');
		$token = $input->getString('token');
		$repo = ServiceFactory::subscribers();
		$subscriber = $repo->findById($id);

		if ($subscriber === null || !ServiceFactory::tokens()->validateUnsubscribeToken($id, $token))
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_UNSUBSCRIBE_TOKEN'), 403);
		}

		$repo->unsubscribe($id, 'one-click');
		$newsletterId = $input->getInt('mid');
		$repo->recordEvent($id, 'one_click_unsubscribe', $input->server->getString('REMOTE_ADDR'), $input->server->getString('HTTP_USER_AGENT'), ['newsletter_id' => $newsletterId]);
		$app->setHeader('Status', '204 No Content', true);
		$app->close();
	}

	/** @return void */
	private function requireFormToken(): void
	{
		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('COM_PUNGAMAIL_ERROR_SECURITY_TOKEN'), 403);
		}
	}

	/** @return array<int,object> */
	private function moduleTopics(int $moduleId): array
	{
		if ($moduleId <= 0)
		{
			// The standalone subscription menu page is not tied to a module and
			// therefore exposes all published topics.
			return ServiceFactory::topics()->active();
		}

		$db = ServiceFactory::database();
		$published = 1;
		$moduleName = 'mod_pungamail_signup';
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__modules'))
			->where($db->quoteName('id') . ' = :id')
			->where($db->quoteName('module') . ' = :module')
			->where($db->quoteName('published') . ' = :published')
			->bind(':id', $moduleId, \Joomla\Database\ParameterType::INTEGER)
			->bind(':module', $moduleName)
			->bind(':published', $published, \Joomla\Database\ParameterType::INTEGER);
		$params = $db->setQuery($query)->loadResult();

		if (!is_string($params))
		{
			return [];
		}

		$configured = array_map('intval', (array) (new Registry($params))->get('topic_ids', []));

		return ServiceFactory::topics()->active($configured !== [] ? $configured : null);
	}

	/** @return string */
	private function safeReturn(): string
	{
		$app = Factory::getApplication();
		$return = base64_decode($app->getInput()->post->getBase64('return'), true);
		$siteRoot = rtrim((string) \Joomla\CMS\Uri\Uri::root(), '/');
		$isLocal = is_string($return)
			&& $return !== ''
			&& ($return === $siteRoot || str_starts_with($return, $siteRoot . '/'));

		return $isLocal
			? $return
			: Route::_('index.php', false);
	}
}
