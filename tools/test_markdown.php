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



$hardBreakMarkdown = <<<'MD'
### Hoffest, 28\.8\.2026  
28 August 2026  
Fotos vom Sommertreffen am 28\. August.

Weiterlesen →
MD;
$hardBreakHtml = $renderer->toHtml($hardBreakMarkdown, 'https://site.example/');
$hardBreakText = $renderer->toText($hardBreakMarkdown, 'https://site.example/');

foreach ([
	'<h3>Hoffest, 28.8.2026</h3>',
	'<p>28 August 2026<br>Fotos vom Sommertreffen am 28. August.</p>',
	'<p>Weiterlesen →</p>',
] as $fragment)
{
	if (!str_contains($hardBreakHtml, $fragment))
	{
		failMarkdownTest('Markdown hard-break/escape HTML is missing: ' . $fragment);
	}
}

if (str_contains($hardBreakHtml, '\\.'))
{
	failMarkdownTest('Markdown HTML still exposes escape backslashes.');
}

if (!str_contains($hardBreakText, 'Hoffest, 28.8.2026') || !str_contains($hardBreakText, '28. August.'))
{
	failMarkdownTest('Markdown plain text still exposes escape backslashes.');
}

$literalMarkdown = <<<'MD'
\*literal stars\* \_literal underscore\_ \#hash \[brackets\]
MD;
$literalHtml = $renderer->toHtml($literalMarkdown, 'https://site.example/');

if (!str_contains($literalHtml, '*literal stars* _literal underscore_ #hash [brackets]'))
{
	failMarkdownTest('Escaped Markdown punctuation was not preserved literally.');
}

if (str_contains($literalHtml, '<em>literal stars</em>') || str_contains($literalHtml, '<em>literal underscore</em>'))
{
	failMarkdownTest('Escaped Markdown punctuation was interpreted as formatting.');
}


$horizontalRuleMarkdown = <<<'MD'
Before

---

After
MD;
$horizontalRuleHtml = $renderer->toHtml($horizontalRuleMarkdown, 'https://site.example/');

if (!str_contains($horizontalRuleHtml, '<p>Before</p>') || !str_contains($horizontalRuleHtml, '<hr>') || !str_contains($horizontalRuleHtml, '<p>After</p>'))
{
	failMarkdownTest('Standalone --- was not rendered as a horizontal rule.');
}

fwrite(STDOUT, "[OK] Markdown renderer smoke tests passed\n");
