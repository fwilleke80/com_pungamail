<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Helper
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/** Adds a lightweight browser warning for unsaved administrator editor changes. */
final class UnsavedChangesHelper
{
	private static bool $loaded = false;

	/** @return void */
	public static function load(): void
	{
		if (self::$loaded)
		{
			return;
		}

		self::$loaded = true;
		Text::script('COM_PUNGAMAIL_UNSAVED_CHANGES_CONFIRM');
		Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript(<<<'JS'
(() =>
{
	document.addEventListener('DOMContentLoaded', function ()
	{
		const form = document.querySelector('form[data-pm-unsaved-warning="1"]');

		if (!form)
		{
			return;
		}

		let cleanSnapshot = null;
		let preEditSnapshot = null;
		let editStarted = false;
		let submitting = false;
		const snapshot = function ()
		{
			const values = [];
			const data = new FormData(form);

			for (const [name, value] of data.entries())
			{
				if (name === 'task')
				{
					continue;
				}

				values.push([name, value instanceof File ? value.name : String(value)]);
			}

			const editorInstances = window.Joomla?.editors?.instances || {};
			Object.entries(editorInstances).forEach(function ([id, editor])
			{
				const element = document.getElementById(id);

				if (!element || !form.contains(element) || typeof editor?.getValue !== 'function')
				{
					return;
				}

				values.push(['__editor__' + id, String(editor.getValue())]);
			});

			return JSON.stringify(values);
		};
		const refreshCleanSnapshot = function ()
		{
			if (!editStarted)
			{
				cleanSnapshot = snapshot();
			}
		};
		const beginEdit = function (baseline = null)
		{
			if (editStarted)
			{
				return;
			}

			cleanSnapshot = baseline ?? preEditSnapshot ?? cleanSnapshot ?? snapshot();
			editStarted = true;
		};
		const belongsToForm = function (target)
		{
			return target instanceof Element && form.contains(target);
		};
		const pointerMayEdit = function (target)
		{
			if (!(target instanceof Element))
			{
				return false;
			}

			if (target.closest('.pm-md-action, #pm-select-visible, #pm-clear-selected'))
			{
				return true;
			}

			let control = target.closest('input, select');
			const label = target.closest('label');

			if (!control && label)
			{
				control = label.control;

				if (!control && label.htmlFor)
				{
					control = document.getElementById(label.htmlFor);
				}
			}

			if (control instanceof HTMLSelectElement)
			{
				return true;
			}

			if (!(control instanceof HTMLInputElement))
			{
				return false;
			}

			return ['checkbox', 'radio', 'range', 'color', 'date', 'datetime-local', 'time', 'month', 'week', 'file', 'number'].includes(control.type);
		};
		const keyMayEdit = function (event)
		{
			if (event.key === 'Tab' || event.key === 'Escape' || event.key === 'Shift' || event.key === 'Control' || event.key === 'Alt' || event.key === 'Meta' || event.key === 'CapsLock')
			{
				return false;
			}

			if (event.key.startsWith('Arrow') || event.key === 'Home' || event.key === 'End' || event.key === 'PageUp' || event.key === 'PageDown')
			{
				return false;
			}

			if (/^F\d{1,2}$/.test(event.key))
			{
				return false;
			}

			return true;
		};
		const isDirty = function ()
		{
			return editStarted && cleanSnapshot !== null && snapshot() !== cleanSnapshot;
		};
		const markSubmitting = function ()
		{
			submitting = true;
			window.setTimeout(function ()
			{
				if (document.visibilityState === 'visible')
				{
					submitting = false;
				}
			}, 1500);
		};
		const confirmDiscard = function ()
		{
			if (!isDirty())
			{
				return true;
			}

			const fallback = 'You have unsaved changes. Leave this editor and discard them?';
			const message = window.Joomla?.Text?._('COM_PUNGAMAIL_UNSAVED_CHANGES_CONFIRM', fallback) || fallback;

			return window.confirm(message);
		};

		form.addEventListener('submit', markSubmitting);
		form.addEventListener('focusin', function ()
		{
			if (!editStarted)
			{
				preEditSnapshot = snapshot();
			}
		}, true);
		form.addEventListener('beforeinput', function (event)
		{
			if (event.isTrusted && belongsToForm(event.target))
			{
				beginEdit(snapshot());
			}
		}, true);
		form.addEventListener('keydown', function (event)
		{
			if (event.isTrusted && belongsToForm(event.target) && keyMayEdit(event))
			{
				beginEdit(snapshot());
			}
		}, true);
		form.addEventListener('pointerdown', function (event)
		{
			if (event.isTrusted && belongsToForm(event.target) && pointerMayEdit(event.target))
			{
				beginEdit(snapshot());
			}
		}, true);
		form.addEventListener('dragstart', function (event)
		{
			if (event.isTrusted && belongsToForm(event.target))
			{
				beginEdit(snapshot());
			}
		}, true);
		form.addEventListener('input', function (event)
		{
			if (event.isTrusted && !editStarted)
			{
				beginEdit(preEditSnapshot ?? cleanSnapshot);
			}
		}, true);
		form.addEventListener('change', function (event)
		{
			if (event.isTrusted && !editStarted)
			{
				beginEdit(preEditSnapshot ?? cleanSnapshot);
			}
		}, true);

		if (window.Joomla && typeof Joomla.submitbutton === 'function')
		{
			const originalSubmitbutton = Joomla.submitbutton;
			Joomla.submitbutton = function (...args)
			{
				const task = String(args[0] || '');

				if (task.endsWith('.cancel') || task === 'newsletter.duplicate')
				{
					if (!confirmDiscard())
					{
						return false;
					}
				}

				markSubmitting();
				return originalSubmitbutton.apply(this, args);
			};
		}

		refreshCleanSnapshot();
		window.requestAnimationFrame(refreshCleanSnapshot);
		window.setTimeout(refreshCleanSnapshot, 100);
		window.setTimeout(refreshCleanSnapshot, 500);
		window.addEventListener('load', refreshCleanSnapshot, {once: true});

		window.addEventListener('beforeunload', function (event)
		{
			if (submitting || !isDirty())
			{
				return;
			}

			event.preventDefault();
			event.returnValue = '';
		});
	});
})();
JS);
	}
}
