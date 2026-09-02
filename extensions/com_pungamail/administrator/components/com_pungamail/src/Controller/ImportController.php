<?php
/**
 * @package     Punga.Mail
 * @subpackage  Administrator.Controller
 * @copyright   Copyright (c) 2026 Punga
 * @license     MIT
 */

namespace Punga\Component\PungaMail\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Punga\Component\PungaMail\Administrator\Service\ServiceFactory;

/** Suppression-safe CSV import and export actions. */
final class ImportController extends BaseController
{
	/** @return void */
	public function preview(): void
	{
		$this->guard();
		$file = Factory::getApplication()->getInput()->files->get('csv_file', null, 'raw');

		try
		{
			if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)
			{
				throw new \RuntimeException(Text::_('COM_PUNGAMAIL_CSV_FILE_REQUIRED'));
			}

			$contents = file_get_contents((string) $file['tmp_name']);

			if ($contents === false)
			{
				throw new \RuntimeException(Text::_('COM_PUNGAMAIL_CSV_READ_FAILED'));
			}

			ServiceFactory::csv()->preview($contents);
			Factory::getApplication()->getSession()->set('pungamail.csv.contents', $contents);
			$this->redirectToImport(Text::_('COM_PUNGAMAIL_CSV_READY'));
		}
		catch (\Throwable $e)
		{
			$this->redirectToImport($e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function commit(): void
	{
		$this->guard();
		$app = Factory::getApplication();
		$contents = (string) $app->getSession()->get('pungamail.csv.contents', '');
		$mapping = (array) $app->getInput()->post->get('mapping', [], 'array');
		$reactivate = $app->getInput()->post->getInt('reactivate', 0) === 1;

		try
		{
			if ($contents === '')
			{
				throw new \RuntimeException(Text::_('COM_PUNGAMAIL_CSV_PREVIEW_FIRST'));
			}

			$result = ServiceFactory::csv()->import($contents, $mapping, $reactivate);
			$app->getSession()->clear('pungamail.csv.contents');
			$this->redirectToImport(Text::sprintf('COM_PUNGAMAIL_IMPORT_RESULT', $result['added'], $result['updated'], $result['unchanged'], $result['skipped'], $result['invalid'], $result['conflicts'], $result['errors']));
		}
		catch (\Throwable $e)
		{
			$this->redirectToImport($e->getMessage(), 'error');
		}
	}

	/** @return void */
	public function clear(): void
	{
		$this->guard();
		Factory::getApplication()->getSession()->clear('pungamail.csv.contents');
		$this->redirectToImport(Text::_('COM_PUNGAMAIL_CSV_CLEARED'));
	}

	/** @return void */
	public function export(): void
	{
		$this->guard();
		$app = Factory::getApplication();
		$scope = $app->getInput()->post->getCmd('scope', 'all');
		$topicIds = array_map('intval', (array) $app->getInput()->post->get('topic_ids', [], 'array'));
		$rows = ServiceFactory::csv()->export($scope, $topicIds);
		$filename = 'pungamail-subscribers-' . gmdate('Y-m-d') . '.csv';
		$app->setHeader('Content-Type', 'text/csv; charset=UTF-8', true);
		$app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
		$app->sendHeaders();
		echo "\xEF\xBB\xBF";
		$stream = fopen('php://output', 'wb');

		if ($stream !== false)
		{
			$headers = $rows !== [] ? array_keys($rows[0]) : ['email', 'name', 'status', 'source', 'language', 'topics', 'suppression_reason', 'bounce_count', 'soft_bounce_count', 'last_bounce_at', 'last_bounce_class', 'last_bounce_reason'];
			fputcsv($stream, $headers);

			foreach ($rows as $row)
			{
				fputcsv($stream, array_values($row));
			}

			fclose($stream);
		}

		$app->close();
	}

	/** @return void */
	private function guard(): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_pungamail'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!Session::checkToken())
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
	}

	/** @return void */
	private function redirectToImport(string $message, string $type = 'message'): void
	{
		$this->setRedirect(Route::_('index.php?option=com_pungamail&view=import', false), $message, $type);
	}
}
