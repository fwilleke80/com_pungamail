<?php
/**
 * @package   Punga.Mail
 * @copyright Copyright (c) 2026 Punga
 * @license   MIT
 */

declare(strict_types=1);

use Punga\Component\PungaMail\Administrator\Service\MarkdownRenderer;

require_once dirname(__DIR__) . '/extensions/com_pungamail/administrator/components/com_pungamail/src/Service/MarkdownRenderer.php';

/**
 * Fails the renderer smoke test.
 *
 * @param string $message Failure description.
 *
 * @return never
 */
function failMarkdownTest(string $message): never
{
	fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
	exit(1);
}

$renderer = new MarkdownRenderer();
$markdown = <<<'MD'
| Name | Value | Note |
| :--- | ---: | :---: |
| **Alpha** | 12 | [Link](https://example.com) |
| Beta \| Gamma | 7 | ok |
MD;
$html = $renderer->toHtml($markdown, 'https://site.example/');
$text = $renderer->toText($markdown, 'https://site.example/');

$requiredHtml = [
	'<table ',
	'<thead>',
	'<tbody>',
	'text-align:left',
	'text-align:right',
	'text-align:center',
	'<strong>Alpha</strong>',
	'<a href="https://example.com">Link</a>',
	'Beta | Gamma',
];

foreach ($requiredHtml as $fragment)
{
	if (!str_contains($html, $fragment))
	{
		failMarkdownTest('Markdown table HTML is missing: ' . $fragment);
	}
}

if (str_contains($html, '\\"'))
{
	failMarkdownTest('Markdown table HTML contains escaped quote artifacts.');
}

$expectedText = "Name\tValue\tNote\nAlpha\t12\tLink (https://example.com)\nBeta | Gamma\t7\tok";

if ($text !== $expectedText)
{
	failMarkdownTest("Unexpected Markdown table plain text:\n" . $text);
}

fwrite(STDOUT, "[OK] Markdown renderer smoke tests passed\n");
