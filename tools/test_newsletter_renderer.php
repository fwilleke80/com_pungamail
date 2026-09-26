<?php
/**
 * @package   Punga.Mail
 * @copyright Copyright (c) 2026 Punga
 * @license   MIT
 */

declare(strict_types=1);

namespace Joomla\CMS\Component
{
	/** Minimal component helper stub for renderer testing. */
	final class ComponentHelper
	{
		/** @return object Parameter stub. */
		public static function getParams(string $option): object
		{
			return new class($option)
			{
				public function __construct(private readonly string $option)
				{
				}

				/** @return mixed */
				public function get(string $key, mixed $default = null): mixed
				{
					return $this->option === 'com_languages' && $key === 'site' ? 'de-DE' : $default;
				}
			};
		}
	}
}


namespace Joomla\CMS\Date
{
	/** Minimal Joomla Date stub with a deterministic current time. */
	class Date extends \DateTime
	{
		public function __construct(string $time = 'now', \DateTimeZone|string|null $timezone = null)
		{
			$timezone = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
			parent::__construct($time === 'now' ? '2026-09-22 10:00:00' : $time, $timezone);
		}

		public function format(string $format, bool $local = false, bool $translate = true): string
		{
			return parent::format($format);
		}
	}
}

namespace Joomla\CMS\Language
{
	interface LanguageFactoryInterface
	{
		public function createLanguage(string $lang, bool $debug = false): Language;
	}

	class Language
	{
		public function load(string $extension, string $basePath, ?string $lang = null, bool $reload = false): bool
		{
			return true;
		}

		public function _(string $key): string
		{
			return match ($key)
			{
				'DATE_FORMAT_LC3' => 'd. F Y',
				'DATE_FORMAT_LC5' => 'd. F Y H:i',
				'SEPTEMBER' => 'September',
				'DECEMBER' => 'Dezember',
				default => $key,
			};
		}

		public function getTag(): string
		{
			return 'de-DE';
		}
	}

	/** Minimal active administrator-language stub for renderer testing. */
	final class Text
	{
		/** @return string */
		public static function _(string $key): string
		{
			return match ($key)
			{
				'COM_PUNGAMAIL_MAIL_FOOTER_REASON' => 'Default footer reason.',
				'COM_PUNGAMAIL_MAIL_UNSUBSCRIBE' => 'Unsubscribe',
				'COM_PUNGAMAIL_MAIL_READ_MORE' => 'Read more',
				'DATE_FORMAT_LC3' => 'd F Y',
				'DATE_FORMAT_LC5' => 'd F Y H:i',
				default => $key,
			};
		}
	}
}

namespace Joomla\CMS\Uri
{
	/** Minimal URI stub for renderer testing. */
	final class Uri
	{
		/** @return string */
		public static function root(): string
		{
			return 'https://site.example/';
		}
	}
}

namespace Joomla\CMS
{
	use Joomla\CMS\Language\Language;
	use Joomla\CMS\Language\LanguageFactoryInterface;

	/** Minimal application factory stub for renderer testing. */
	final class Factory
	{
		/** @return object */
		public static function getApplication(): object
		{
			return new class
			{
				/** @return mixed */
				public function get(string $key, mixed $default = null): mixed
				{
					return match ($key)
					{
						'sitename' => 'Test Site',
						'offset' => 'UTC',
						'language' => 'en-GB',
						default => $default,
					};
				}
			};
		}

		/** @return \Joomla\CMS\Date\Date */
		public static function getDate(string $time = 'now', string $timezone = 'UTC'): \Joomla\CMS\Date\Date
		{
			return new \Joomla\CMS\Date\Date($time, $timezone);
		}

		public static function getContainer(): object
		{
			return new class
			{
				public function get(string $class): object
				{
					if ($class !== LanguageFactoryInterface::class)
					{
						throw new \RuntimeException('Unexpected container lookup: ' . $class);
					}

					return new class implements LanguageFactoryInterface
					{
						public function createLanguage(string $lang, bool $debug = false): Language
						{
							return new Language();
						}
					};
				}
			};
		}
	}
}


namespace Punga\Component\PungaMail\Administrator\Service
{
	/** ASCII-safe mb_strlen fallback for the isolated CLI test environment. */
	function mb_strlen(string $value, ?string $encoding = null): int
	{
		return \strlen($value);
	}

	/** ASCII-safe mb_substr fallback for the isolated CLI test environment. */
	function mb_substr(string $value, int $offset, ?int $length = null, ?string $encoding = null): string
	{
		return $length === null ? \substr($value, $offset) : \substr($value, $offset, $length);
	}
}

namespace
{
	use Punga\Component\PungaMail\Administrator\Service\ContentLayoutRepository;
	use Punga\Component\PungaMail\Administrator\Service\ContentTypeService;
	use Punga\Component\PungaMail\Administrator\Service\MailConfigurationService;
	use Punga\Component\PungaMail\Administrator\Service\MailStyleService;
	use Punga\Component\PungaMail\Administrator\Service\MailTextService;
	use Punga\Component\PungaMail\Administrator\Service\MarkdownRenderer;
	use Punga\Component\PungaMail\Administrator\Service\NewsletterRenderer;
	use Punga\Component\PungaMail\Administrator\Service\SiteDateService;
	use Punga\Component\PungaMail\Administrator\Service\TemplateRepository;
	use Punga\Component\PungaMail\Administrator\Service\UserFieldService;

	define('JPATH_SITE', sys_get_temp_dir());
	$serviceRoot = dirname(__DIR__) . '/extensions/com_pungamail/administrator/components/com_pungamail/src/Service/';
	require_once $serviceRoot . 'MarkdownRenderer.php';
	require_once $serviceRoot . 'ContentTypeService.php';
	require_once $serviceRoot . 'ContentLayoutRepository.php';
	require_once $serviceRoot . 'MailStyleService.php';
	require_once $serviceRoot . 'MailConfigurationService.php';
	require_once $serviceRoot . 'MailTextService.php';
	require_once $serviceRoot . 'TemplateRepository.php';
	require_once $serviceRoot . 'UserFieldService.php';
	require_once $serviceRoot . 'SiteDateService.php';
	require_once $serviceRoot . 'NewsletterRenderer.php';

	/**
	 * Fails the newsletter-renderer regression test.
	 *
	 * @param string $message Failure description.
	 *
	 * @return never
	 */
	function failNewsletterRendererTest(string $message): never
	{
		fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
		exit(1);
	}

	$contentTypesReflection = new \ReflectionClass(ContentTypeService::class);
	/** @var ContentTypeService $contentTypes */
	$contentTypes = $contentTypesReflection->newInstanceWithoutConstructor();
	$typesProperty = $contentTypesReflection->getProperty('types');
	$typesProperty->setValue($contentTypes, []);

	$templateReflection = new \ReflectionClass(TemplateRepository::class);
	/** @var TemplateRepository $templates */
	$templates = $templateReflection->newInstanceWithoutConstructor();
	$mailConfigurationReflection = new \ReflectionClass(MailConfigurationService::class);
	/** @var MailConfigurationService $mailConfiguration */
	$mailConfiguration = $mailConfigurationReflection->newInstanceWithoutConstructor();
	$contentLayoutsReflection = new \ReflectionClass(ContentLayoutRepository::class);
	/** @var ContentLayoutRepository $contentLayouts */
	$contentLayouts = $contentLayoutsReflection->newInstanceWithoutConstructor();
	$layoutCache = $contentLayoutsReflection->getProperty('cache');
	$layoutCache->setValue($contentLayouts, [
		ContentLayoutRepository::DEFAULT_KEY => (object) ['layout_markdown' => ContentLayoutRepository::DEFAULT_LAYOUT],
		'com_example.item' => null,
	]);
	$userFieldsReflection = new \ReflectionClass(UserFieldService::class);
	/** @var UserFieldService $userFields */
	$userFields = $userFieldsReflection->newInstanceWithoutConstructor();

	$renderer = new NewsletterRenderer(
		new MarkdownRenderer(),
		$contentTypes,
		new MailStyleService(),
		$templates,
		new MailTextService(),
		$mailConfiguration,
		$contentLayouts,
		$userFields,
		new SiteDateService()
	);
	$newsletter = (object) [
		'subject' => 'Renderer test {date} for {recipient}',
		'body_markdown' => "# Intro\n\nDate: {date}. Hello {recipient}.\n\n{new_content}\n\nAfter the selected content.",
		'template_id' => 0,
		'style_overrides' => null,
		'custom_css' => null,
	];
	$items = [
		(object) [
			'source_key' => 'com_example.item',
			'source_item_id' => '42',
			'title_override' => '',
			'excerpt_override' => '',
			'snapshot_title' => 'Selected item',
			'snapshot_excerpt' => 'Selected excerpt.',
			'snapshot_url' => 'https://site.example/item/42',
		],
	];
	$result = $renderer->render($newsletter, $items);

	foreach (['html', 'text'] as $format)
	{
		$output = $result[$format];

		if (!str_contains($output, 'Selected item'))
		{
			failNewsletterRendererTest($format . ' output does not contain selected new content.');
		}

		if (str_contains($output, '{new_content}') || str_contains($output, 'PUNGAMAILNEWCONTENT') || str_contains($output, 'PUNGAMAIL_NEW_CONTENT'))
		{
			failNewsletterRendererTest($format . ' output leaked the new-content placeholder or an internal marker.');
		}
	}

	if (!str_contains($result['html'], '<h1') || !str_contains($result['html'], 'After the selected content.'))
	{
		failNewsletterRendererTest('HTML around the new-content block was not preserved.');
	}

	if (!str_contains($result['html'], 'Default footer reason.'))
	{
		failNewsletterRendererTest('Localized/default footer reason was not included in HTML output.');
	}

	if (!str_contains($result['html'], NewsletterRenderer::RECIPIENT_PLACEHOLDER))
	{
		failNewsletterRendererTest('Recipient placeholder was resolved before recipient-specific delivery.');
	}

	if (str_contains($result['subject'], NewsletterRenderer::DATE_PLACEHOLDER) || str_contains($result['html'], NewsletterRenderer::DATE_PLACEHOLDER) || !str_contains($result['subject'], '22. September 2026'))
	{
		failNewsletterRendererTest('General {date} placeholder was not resolved with the site-facing date formatter.');
	}

	$layoutCache->setValue($contentLayouts, [
		ContentLayoutRepository::DEFAULT_KEY => (object) ['layout_markdown' => ContentLayoutRepository::DEFAULT_LAYOUT],
		'com_example.item' => (object) ['layout_markdown' => "## {title_link}\n\nSource: {content_type}\n\n{excerpt}\n\n{read_more}"],
	]);
	$customResult = $renderer->render($newsletter, $items);

	if (!str_contains($customResult['html'], '<h2') || !str_contains($customResult['html'], 'com_example.item'))
	{
		failNewsletterRendererTest('Central content-type Markdown layout was not applied.');
	}

	if (!str_contains($customResult['html'], 'Read more') || !str_contains($customResult['html'], 'https://site.example/item/42'))
	{
		failNewsletterRendererTest('Selected-content link placeholders were not rendered.');
	}

	$renderItem = (new \ReflectionClass(NewsletterRenderer::class))->getMethod('renderContentItemTemplate');
	$databasePlaceholder = $renderItem->invoke(
		$renderer,
		"{title} — {venue} — {missing}",
		['title' => 'Overridden title'],
		['title' => 'Raw title', 'venue' => 'Main Hall']
	);

	if ($databasePlaceholder !== 'Overridden title — Main Hall — {missing}')
	{
		failNewsletterRendererTest('Database placeholders or generic-placeholder precedence are incorrect.');
	}

	$rangePlaceholder = $renderItem->invoke(
		$renderer,
		"{start_at|date_range:{end_at}}\n{start_at|time_range:{end_at}}\n{start_at|period:{end_at},{all_day}}",
		[],
		[
			'start_at' => '2026-09-22 19:00:00',
			'end_at' => '2026-09-22 21:00:00',
			'all_day' => '0',
		]
	);

	if ($rangePlaceholder !== "22\\. September 2026\n19:00–21:00\n22\\. September 2026, 19:00–21:00")
	{
		failNewsletterRendererTest('Generic date_range/time_range/period formatters did not resolve nested placeholder arguments.');
	}

	$allDayPeriod = $renderItem->invoke(
		$renderer,
		'{start_at|period:{end_at},{all_day}}',
		[],
		[
			'start_at' => '2026-09-22 00:00:00',
			'end_at' => '2026-09-24 00:00:00',
			'all_day' => '1',
		]
	);

	if ($allDayPeriod !== '22\\.–23\\. September 2026')
	{
		failNewsletterRendererTest('All-day period formatter did not suppress times or compact a same-month date range.');
	}

	$siteLanguagePeriod = $renderItem->invoke(
		$renderer,
		'{start_at|period:{end_at},{all_day}}',
		[],
		[
			'start_at' => '2026-12-29 00:00:00',
			'end_at' => '2026-12-30 00:00:00',
			'all_day' => '1',
		]
	);

	if ($siteLanguagePeriod !== '29\\. Dezember 2026')
	{
		failNewsletterRendererTest('Content-layout date formatting leaked the active administrator language instead of using the site language.');
	}

	$singleDayAllDayPeriod = $renderItem->invoke(
		$renderer,
		'{start_at|period:{end_at},{all_day}}',
		[],
		[
			'start_at' => '2026-12-24 00:00:00',
			'end_at' => '2026-12-25 00:00:00',
			'all_day' => '1',
		]
	);

	if ($singleDayAllDayPeriod !== '24\\. Dezember 2026')
	{
		failNewsletterRendererTest('Single-day all-day period displayed its exclusive end boundary.');
	}

	$multiDayAllDayPeriod = $renderItem->invoke(
		$renderer,
		'{start_at|period:{end_at},{all_day}}',
		[],
		[
			'start_at' => '2026-10-12 00:00:00',
			'end_at' => '2026-10-24 00:00:00',
			'all_day' => '1',
		]
	);

	if ($multiDayAllDayPeriod !== '12\\.–23\\. October 2026')
	{
		failNewsletterRendererTest('Multi-day all-day period displayed its exclusive end boundary.');
	}

	$literalRange = $renderItem->invoke(
		$renderer,
		'{start_at|date_range:2026-09-24 00:00:00}',
		[],
		['start_at' => '2026-09-22 00:00:00']
	);

	if ($literalRange !== '22\\.–24\\. September 2026')
	{
		failNewsletterRendererTest('Range formatter literal arguments are not supported.');
	}

	$unknownNested = $renderItem->invoke(
		$renderer,
		'{start_at|date_range:{missing_end}}',
		[],
		['start_at' => '2026-09-22 00:00:00']
	);

	if ($unknownNested !== '{start_at|date_range:{missing_end}}')
	{
		failNewsletterRendererTest('Unknown nested formatter placeholders should remain visible for diagnosis.');
	}

	$personalized = $renderer->personalize(
		$result['subject'],
		$result['html'],
		$result['text'],
		'Alice & Bob'
	);

	if ($personalized['subject'] !== 'Renderer test 22. September 2026 for Alice & Bob')
	{
		failNewsletterRendererTest('Recipient placeholder was not resolved in the subject.');
	}

	if (!str_contains($personalized['html'], 'Hello Alice &amp; Bob.') || str_contains($personalized['html'], NewsletterRenderer::RECIPIENT_PLACEHOLDER))
	{
		failNewsletterRendererTest('Recipient placeholder was not safely resolved in HTML output.');
	}

	if (!str_contains($personalized['text'], 'Hello Alice & Bob.') || str_contains($personalized['text'], NewsletterRenderer::RECIPIENT_PLACEHOLDER))
	{
		failNewsletterRendererTest('Recipient placeholder was not resolved in plain-text output.');
	}

	$replaceUserField = (new \ReflectionClass(NewsletterRenderer::class))->getMethod('replaceUserFieldPlaceholders');
	$fieldValues = ['mobile-phone' => '+49 30 <123>', 'department' => 'Engineering'];
	$fieldHtml = $replaceUserField->invoke($renderer, 'Call {userfield|mobile-phone}; {userfield|missing}', $fieldValues, true);
	$fieldText = $replaceUserField->invoke($renderer, '{userfield|department} / {userfield|missing}', $fieldValues, false);

	if ($fieldHtml !== 'Call +49 30 &lt;123&gt;; ')
	{
		failNewsletterRendererTest('Joomla User Custom Field placeholder was not HTML-escaped or unknown alias was not cleared.');
	}

	if ($fieldText !== 'Engineering / ')
	{
		failNewsletterRendererTest('Joomla User Custom Field placeholder was not resolved in plain text.');
	}


	$pluginNewsletter = clone $newsletter;
	$pluginNewsletter->style_overrides = json_encode([
		'heading_background' => '#ffeecc',
		'header_background_image_mode' => 'custom',
		'header_background_image' => 'images/header-bg.jpg',
		'header_background_image_behavior' => 'tile_x',
		'mail_heading_color' => '#123456',
		'logo_mode' => 'custom',
		'logo_url' => 'images/logo.png',
		'logo_width' => '140',
		'footer_background' => '#f7f7f7',
		'footer_background_image_mode' => 'custom',
		'footer_background_image' => 'images/footer-bg.jpg',
		'footer_link_color' => '#654321',
	]);
	$pluginItems = [
		(object) [
			'source_key' => 'com_example.item',
			'source_item_id' => '43',
			'title_override' => '',
			'excerpt_override' => '',
			'snapshot_title' => 'Plugin excerpt item',
			'snapshot_excerpt' => 'Before {snippet alias="wichtig"} after {1, 2, 3}. {box}Inside{/box}',
			'snapshot_url' => 'https://site.example/item/43',
		],
	];
	$pluginResult = $renderer->render($pluginNewsletter, $pluginItems);

	if (str_contains($pluginResult['html'], '{snippet') || str_contains($pluginResult['html'], '{box}') || str_contains($pluginResult['html'], '{/box}'))
	{
		failNewsletterRendererTest('Unresolved Joomla-style content-plugin commands leaked into the excerpt.');
	}

	if (!str_contains($pluginResult['html'], 'Before after {1, 2, 3}. Inside'))
	{
		failNewsletterRendererTest('Excerpt plugin sanitization removed ordinary brace text or readable paired-plugin content.');
	}

	if (!str_contains($pluginResult['html'], 'width:100%') || !str_contains($pluginResult['html'], 'background-color:#ffeecc'))
	{
		failNewsletterRendererTest('Mail heading is not a full-width block with the configured background colour.');
	}

	if (!str_contains($pluginResult['html'], 'color:#123456'))
	{
		failNewsletterRendererTest('Mail heading does not use its independently configured text colour.');
	}

	if (!str_contains($pluginResult['html'], 'background="https://site.example/images/header-bg.jpg"') || !str_contains($pluginResult['html'], 'background="https://site.example/images/footer-bg.jpg"'))
	{
		failNewsletterRendererTest('Header/footer background images were not normalized and emitted with email-compatible background attributes.');
	}

	if (!str_contains($pluginResult['html'], 'background-repeat:repeat-x;background-position:left top;background-size:auto;'))
	{
		failNewsletterRendererTest('Header background image display behavior was not rendered.');
	}

	$headerStart = strpos($pluginResult['html'], '<table class="pm-mail-heading"');
	$headerEnd = $headerStart !== false ? strpos($pluginResult['html'], '</table>', $headerStart) : false;
	$logoPosition = strpos($pluginResult['html'], 'class="pm-mail-logo-image"');

	if ($headerStart === false || $headerEnd === false || $logoPosition === false || $logoPosition < $headerStart || $logoPosition > $headerEnd)
	{
		failNewsletterRendererTest('Logo is not rendered inside the mail header region.');
	}

	if (!str_contains($pluginResult['html'], 'color:#654321'))
	{
		failNewsletterRendererTest('Footer-specific link colour was not applied.');
	}

	$styles = new MailStyleService();
	$inherited = $styles->resolve(
		json_encode([
			'header_alignment' => 'center',
			'footer_background' => '#111111',
			'footer_background_image_mode' => 'custom',
			'footer_background_image' => 'images/template-footer.jpg',
		]),
		json_encode([
			'footer_background' => '#222222',
			'footer_background_image_mode' => 'none',
		]),
		null,
		null
	);

	if ($inherited['header_alignment'] !== 'center' || $inherited['footer_background'] !== '#222222' || $inherited['footer_background_image'] !== '')
	{
		failNewsletterRendererTest('Per-field Component/Template/Newsletter layout inheritance is incorrect.');
	}

	fwrite(STDOUT, "[OK] Newsletter renderer new-content/recipient regression test passed\n");
}
