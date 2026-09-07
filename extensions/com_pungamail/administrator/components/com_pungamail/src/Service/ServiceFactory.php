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

	/** @return CheckoutService */
	public static function checkouts(): CheckoutService
	{
		return new CheckoutService(self::database());
	}

	/** @return SubscriberRepository */
	public static function subscribers(): SubscriberRepository
	{
		return new SubscriberRepository(self::database());
	}

	/** @return TopicRepository */
	public static function topics(): TopicRepository
	{
		return new TopicRepository(self::database());
	}

	/** @return MailSettingsRepository */
	public static function mailSettings(): MailSettingsRepository
	{
		return new MailSettingsRepository(self::database(), new SecretService());
	}

	/** @return MailConfigurationService */
	public static function mailConfiguration(): MailConfigurationService
	{
		return new MailConfigurationService(self::mailSettings());
	}

	/** @return BounceService */
	public static function bounces(): BounceService
	{
		return new BounceService(self::database(), self::mailSettings(), self::subscribers(), self::newsletters());
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

	/** @return ContentLayoutRepository */
	public static function contentLayouts(): ContentLayoutRepository
	{
		return new ContentLayoutRepository(self::database());
	}

	/** @return UserFieldService */
	public static function userFields(): UserFieldService
	{
		return new UserFieldService(self::database());
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
		return new NewsletterRenderer(new MarkdownRenderer(), self::contentTypes(), self::styles(), self::templates(), new MailTextService(), self::mailConfiguration(), self::contentLayouts(), self::userFields());
	}

	/** @return RecipientResolver */
	public static function recipients(): RecipientResolver
	{
		return new RecipientResolver(self::database(), self::subscribers());
	}

	/** @return MailService */
	public static function mail(): MailService
	{
		return new MailService(Factory::getContainer()->get(MailerFactoryInterface::class), self::tokens(), new MarkdownRenderer(), self::renderer(), self::database(), self::mailConfiguration(), self::mailSettings());
	}

	/** @return QueueService */
	public static function queue(): QueueService
	{
		return new QueueService(self::database(), self::newsletters(), self::renderer(), self::recipients(), self::preflight(), self::mailConfiguration(), self::templates());
	}

	/** @return PreflightService */
	public static function preflight(): PreflightService
	{
		return new PreflightService(self::newsletters(), self::recipients(), self::renderer(), self::contentTypes(), self::templates(), self::mailConfiguration());
	}

	/** @return QueueProcessor */
	public static function processor(): QueueProcessor
	{
		return new QueueProcessor(self::database(), self::newsletters(), self::mail());
	}

	/** @return ScheduledSendService */
	public static function scheduledSends(): ScheduledSendService
	{
		return new ScheduledSendService(self::newsletters(), self::queue());
	}

	/** @return DigestRepository */
	public static function digests(): DigestRepository
	{
		return new DigestRepository(self::database());
	}

	/** @return DigestService */
	public static function digestProcessor(): DigestService
	{
		return new DigestService(self::digests(), self::newsletters(), self::templates(), self::contentTypes(), self::recipients(), self::queue(), self::mail());
	}

	/** @return StatisticsService */
	public static function statistics(): StatisticsService
	{
		return new StatisticsService(self::database());
	}

	/** @return CsvService */
	public static function csv(): CsvService
	{
		return new CsvService(self::database(), self::subscribers(), self::topics());
	}

	/** @return ReminderService */
	public static function reminder(): ReminderService
	{
		return new ReminderService(self::database(), self::mail());
	}
}
