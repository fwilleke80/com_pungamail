<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Helper
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Helper;

use Joomla\CMS\Editor\EditorsRegistry;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** Renders Punga Mail's Markdown source editor on top of Joomla's CodeMirror provider. */
final class MarkdownEditorHelper
{
	private static bool $assetsLoaded = false;

	/**
	 * Renders a Markdown editor with formatting controls, placeholders and live preview.
	 *
	 * @param string        $name         Form control name.
	 * @param string        $id           Unique HTML id.
	 * @param string        $value        Markdown source.
	 * @param int           $rows         Editor row hint.
	 * @param array<string> $placeholders Available placeholders.
	 * @param array<string> $help         Detailed help text lines.
	 *
	 * @return string Editor HTML.
	 */
	public static function render(string $name, string $id, string $value, int $rows = 12, array $placeholders = [], array $help = []): string
	{
		self::loadAssets();
		$editor = self::renderSourceEditor($name, $id, $value, $rows);
		$placeholderOptions = '';

		foreach ($placeholders as $placeholder)
		{
			$escaped = htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8');
			$placeholderOptions .= '<option value="' . $escaped . '">' . $escaped . '</option>';
		}

		$placeholderSelect = $placeholderOptions !== ''
			? '<select class="form-select form-select-sm pm-md-placeholder" aria-label="' . htmlspecialchars(Text::_('COM_PUNGAMAIL_MARKDOWN_INSERT_PLACEHOLDER'), ENT_QUOTES, 'UTF-8') . '">'
				. '<option value="">' . htmlspecialchars(Text::_('COM_PUNGAMAIL_MARKDOWN_PLACEHOLDERS'), ENT_QUOTES, 'UTF-8') . '</option>'
				. $placeholderOptions . '</select>'
			: '';
		$helpHtml = '';

		if ($help !== [])
		{
			$lines = '';

			foreach ($help as $line)
			{
				if (trim($line) !== '')
				{
					$lines .= '<div class="mb-1">' . $line . '</div>';
				}
			}

			if ($lines !== '')
			{
				$helpHtml = '<details class="pm-md-help mt-2"><summary><span class="pm-md-chevron" aria-hidden="true">▶</span> '
					. htmlspecialchars(Text::_('COM_PUNGAMAIL_MARKDOWN_HELP_DETAILS'), ENT_QUOTES, 'UTF-8')
					. '</summary><div class="form-text pt-2">' . $lines . '</div></details>';
			}
		}

		return '<div class="pm-markdown-editor" data-editor-id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">'
			. '<div class="pm-md-toolbar d-flex flex-wrap gap-1 align-items-center mb-2" role="toolbar" aria-label="' . htmlspecialchars(Text::_('COM_PUNGAMAIL_MARKDOWN_TOOLBAR'), ENT_QUOTES, 'UTF-8') . '">'
			. self::button('bold', '<strong>B</strong>', Text::_('COM_PUNGAMAIL_MARKDOWN_BOLD'))
			. self::button('italic', '<em>I</em>', Text::_('COM_PUNGAMAIL_MARKDOWN_ITALIC'))
			. self::button('h2', 'H2', Text::_('COM_PUNGAMAIL_MARKDOWN_HEADING_2'))
			. self::button('h3', 'H3', Text::_('COM_PUNGAMAIL_MARKDOWN_HEADING_3'))
			. self::button('link', '🔗', Text::_('COM_PUNGAMAIL_MARKDOWN_LINK'))
			. self::button('ul', '•', Text::_('COM_PUNGAMAIL_MARKDOWN_BULLET_LIST'))
			. self::button('ol', '1.', Text::_('COM_PUNGAMAIL_MARKDOWN_NUMBERED_LIST'))
			. self::button('quote', '❯', Text::_('COM_PUNGAMAIL_MARKDOWN_QUOTE'))
			. self::button('hr', '—', Text::_('COM_PUNGAMAIL_MARKDOWN_HORIZONTAL_RULE'))
			. $placeholderSelect
			. '<span class="ms-auto btn-group btn-group-sm" role="group">'
			. '<button type="button" class="btn btn-secondary active pm-md-mode" data-mode="edit">' . htmlspecialchars(Text::_('COM_PUNGAMAIL_MARKDOWN_EDIT'), ENT_QUOTES, 'UTF-8') . '</button>'
			. '<button type="button" class="btn btn-secondary pm-md-mode" data-mode="preview">' . htmlspecialchars(Text::_('COM_PUNGAMAIL_MARKDOWN_PREVIEW'), ENT_QUOTES, 'UTF-8') . '</button>'
			. '</span></div>'
			. '<div class="pm-md-source">' . $editor . '</div>'
			. '<div class="pm-md-preview border rounded p-3 d-none" aria-live="polite"></div>'
			. $helpHtml
			. '</div>';
	}

	/** @return string */
	private static function renderSourceEditor(string $name, string $id, string $value, int $rows): string
	{
		try
		{
			/** @var EditorsRegistry $registry */
			$registry = Factory::getContainer()->get(EditorsRegistry::class);
			$registry->initRegistry();

			if ($registry->has('codemirror'))
			{
				return $registry->get('codemirror')->display(
					$name,
					htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8'),
					['width' => '100%', 'height' => max(180, $rows * 22) . 'px', 'row' => $rows, 'id' => $id],
					['buttons' => false, 'syntax' => 'markdown']
				);
			}
		}
		catch (\Throwable)
		{
			// A plain source textarea remains fully functional when CodeMirror is unavailable.
		}

		return '<textarea class="form-control font-monospace" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
			. '" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" rows="' . $rows . '">'
			. htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8') . '</textarea>';
	}

	/** @return string */
	private static function button(string $action, string $content, string $title): string
	{
		return '<button type="button" class="btn btn-outline-secondary btn-sm pm-md-action" data-action="' . $action
			. '" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" aria-label="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
			. $content . '</button>';
	}

	/** @return void */
	private static function loadAssets(): void
	{
		if (self::$assetsLoaded)
		{
			return;
		}

		self::$assetsLoaded = true;
		$document = Factory::getApplication()->getDocument();
		$wa = $document->getWebAssetManager();
		$previewUrl = Route::_('index.php?option=com_pungamail&task=markdown.preview&format=json', false);
		$token = Session::getFormToken();
		$document->addScriptOptions('com_pungamail.markdownEditor', [
			'previewUrl' => $previewUrl,
			'token' => $token,
			'previewError' => Text::_('COM_PUNGAMAIL_MARKDOWN_PREVIEW_ERROR'),
		]);
		$wa->addInlineStyle('.pm-markdown-editor .pm-md-placeholder{width:auto;min-width:11rem}.pm-md-preview{min-height:10rem;background:var(--body-bg,#fff)}.pm-md-help>summary{cursor:pointer;list-style:none}.pm-md-help>summary::-webkit-details-marker{display:none}.pm-md-chevron{display:inline-block;transition:transform .15s ease}.pm-md-help[open] .pm-md-chevron{transform:rotate(90deg)}.pm-markdown-editor joomla-editor-codemirror{display:block}.pm-markdown-editor .cm-editor{font-family:var(--font-monospace,monospace)}');
		$wa->addInlineScript(<<<'JS'
(() => {
	const options = Joomla.getOptions('com_pungamail.markdownEditor', {});
	const getEditor = (id) => (window.Joomla && Joomla.editors && Joomla.editors.instances) ? Joomla.editors.instances[id] : null;
	const getTextarea = (root) => root.querySelector('textarea');
	const getValue = (root) => {
		const editor = getEditor(root.dataset.editorId);
		return editor && typeof editor.getValue === 'function' ? editor.getValue() : (getTextarea(root)?.value || '');
	};
	const replaceSelection = (root, transform) => {
		const editor = getEditor(root.dataset.editorId);
		if (editor && typeof editor.getSelection === 'function' && typeof editor.replaceSelection === 'function') {
			const selected = editor.getSelection() || '';
			editor.replaceSelection(transform(selected));
			return;
		}
		const textarea = getTextarea(root);
		if (!textarea) return;
		const start = textarea.selectionStart ?? textarea.value.length;
		const end = textarea.selectionEnd ?? start;
		const selected = textarea.value.slice(start, end);
		const replacement = transform(selected);
		textarea.setRangeText(replacement, start, end, 'end');
		textarea.dispatchEvent(new Event('input', {bubbles: true}));
		textarea.focus();
	};
	const prefixLines = (text, prefix) => (text || 'text').split('\n').map((line) => `${prefix}${line}`).join('\n');
	const transforms = {
		bold: (s) => `**${s || 'text'}**`,
		italic: (s) => `*${s || 'text'}*`,
		h2: (s) => prefixLines(s, '## '),
		h3: (s) => prefixLines(s, '### '),
		link: (s) => `[${s || 'link text'}](https://)`,
		ul: (s) => prefixLines(s, '- '),
		ol: (s) => (s || 'text').split('\n').map((line, i) => `${i + 1}. ${line}`).join('\n'),
		quote: (s) => prefixLines(s, '> '),
		hr: () => '\n---\n'
	};
	const showEdit = (root) => {
		root.querySelector('.pm-md-source')?.classList.remove('d-none');
		root.querySelector('.pm-md-preview')?.classList.add('d-none');
		root.querySelectorAll('.pm-md-mode').forEach((button) => button.classList.toggle('active', button.dataset.mode === 'edit'));
	};
	const showPreview = async (root) => {
		const preview = root.querySelector('.pm-md-preview');
		if (!preview || !options.previewUrl || !options.token) return;
		preview.classList.remove('d-none');
		root.querySelector('.pm-md-source')?.classList.add('d-none');
		root.querySelectorAll('.pm-md-mode').forEach((button) => button.classList.toggle('active', button.dataset.mode === 'preview'));
		preview.textContent = '…';
		const body = new URLSearchParams({markdown: getValue(root)});
		body.set(options.token, '1');
		try {
			const response = await fetch(options.previewUrl, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body});
			const json = await response.json();
			if (!response.ok || json.success === false) throw new Error(json.message || options.previewError || 'Preview failed');
			preview.innerHTML = json.data?.html || '';
		} catch (error) {
			preview.textContent = error.message || options.previewError || 'Preview failed';
		}
	};
	document.addEventListener('click', (event) => {
		const action = event.target.closest('.pm-md-action');
		if (action) {
			const root = action.closest('.pm-markdown-editor');
			const transform = transforms[action.dataset.action];
			if (root && transform) replaceSelection(root, transform);
			return;
		}
		const mode = event.target.closest('.pm-md-mode');
		if (mode) {
			const root = mode.closest('.pm-markdown-editor');
			if (!root) return;
			mode.dataset.mode === 'preview' ? showPreview(root) : showEdit(root);
		}
	});
	document.addEventListener('change', (event) => {
		const select = event.target.closest('.pm-md-placeholder');
		if (!select || !select.value) return;
		const root = select.closest('.pm-markdown-editor');
		if (root) replaceSelection(root, () => select.value);
		select.value = '';
	});
})();
JS);
	}
}
