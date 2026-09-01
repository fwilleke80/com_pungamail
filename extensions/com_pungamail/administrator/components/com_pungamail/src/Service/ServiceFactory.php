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

	/** @return ContentTypeService */
	public static function contentTypes(): ContentTypeService
	{
		return new ContentTypeService(self::database());
	}

	/** @return TemplateRepository */
	public static function templates(): TemplateRepository
	{
		return new TemplateRepository(self::database());
	}

	/** @return MailStyleService */
	public static function styles(): MailStyleService
	{
		return new MailStyleService();
	}

	/** @return NewsletterRenderer */
	public static function renderer(): NewsletterRenderer
	{
		return new NewsletterRenderer(new MarkdownRenderer(), self::contentTypes(), self::styles(), self::templates(), new MailTextService());
	}

	/** @return RecipientResolver */
	public static function recipients(): RecipientResolver
	{
		return new RecipientResolver(self::database(), self::subscribers());
	}

	/** @return MailService */
	public static function mail(): MailService
	{
		return new MailService(Factory::getContainer()->get(MailerFactoryInterface::class), self::tokens(), new MarkdownRenderer(), self::renderer(), self::database());
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

	/** @return ReminderService */
	public static function reminder(): ReminderService
	{
		return new ReminderService(self::database(), self::mail());
	}
}
