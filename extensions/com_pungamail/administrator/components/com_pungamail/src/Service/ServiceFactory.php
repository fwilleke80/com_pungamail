<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Service
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\Database\DatabaseInterface;

/**
 * Small composition root for Punga Mail services.
 *
 * Joomla owns the infrastructure services; this class wires the component's
 * deliberately dependency-light domain services without global mutable state.
 */
final class ServiceFactory
{
	/**
	 * Returns the Joomla database service.
	 *
	 * @return DatabaseInterface Database connection.
	 */
	public static function database(): DatabaseInterface
	{
		return Factory::getContainer()->get(DatabaseInterface::class);
	}

	/** @return SubscriberRepository */
	public static function subscribers(): SubscriberRepository
	{
		return new SubscriberRepository(self::database());
	}

	/** @return NewsletterRepository */
	public static function newsletters(): NewsletterRepository
	{
		return new NewsletterRepository(self::database());
	}

	/** @return TokenService */
	public static function tokens(): TokenService
	{
		return new TokenService();
	}

	/** @return NewsletterRenderer */
	public static function renderer(): NewsletterRenderer
	{
		return new NewsletterRenderer(new MarkdownRenderer(), self::newsletters());
	}

	/** @return RecipientResolver */
	public static function recipients(): RecipientResolver
	{
		return new RecipientResolver(self::database(), self::subscribers());
	}

	/** @return MailService */
	public static function mail(): MailService
	{
		return new MailService(Factory::getContainer()->get(MailerFactoryInterface::class), self::tokens(), new MarkdownRenderer());
	}

	/** @return QueueService */
	public static function queue(): QueueService
	{
		return new QueueService(self::database(), self::newsletters(), self::renderer(), self::recipients());
	}

	/** @return QueueProcessor */
	public static function processor(): QueueProcessor
	{
		return new QueueProcessor(self::database(), self::newsletters(), self::mail());
	}
}
