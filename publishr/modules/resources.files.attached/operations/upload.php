<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class resources_files_attached__upload_WdOperation extends WdOperation
{
	/**
	 * @var WdUploaded
	 */
	protected $file;

	protected function validate()
	{
		#
		# we set the HTTP_ACCEPT ourselves to force JSON output
		#

		$_SERVER['HTTP_ACCEPT'] = 'application/json';

		#
		# TODO-20100624: we use 'Filedata' because it's used by Swiff.Uploader, we have to change
		# that as soon as possible.
		#

		#
		# TODO-20100624: we should use the `accept` parameter.
		#

		$file = new WdUploaded
		(
			'Filedata', /*array
			(
				'image/jpeg',
				'image/gif',
				'image/png',

				'txt' => 'text/plain',
				'doc' => 'application/msword',
				'xls' => 'application/vnd.ms-excel',
				'pdf' => 'application/pdf',
				'ppt' => 'application/vnd.ms-powerpoint',
				'pps' => 'application/vnd.ms-powerpoint',

				'odt' => 'application/vnd.oasis.opendocument.text', // Texte formaté
				'ods' => 'application/vnd.oasis.opendocument.spreadsheet', // Tableur
				'odp' => 'application/vnd.oasis.opendocument.presentation', // Présentation
				'odg' => 'application/vnd.oasis.opendocument.graphics', // Dessin
				'odc' => 'application/vnd.oasis.opendocument.chart', // Diagramme
				'odf' => 'application/vnd.oasis.opendocument.formula', // Formule
				'odb' => 'application/vnd.oasis.opendocument.database', // Base de données
				'odi' => 'application/vnd.oasis.opendocument.image', // Image
				'odm' => 'application/vnd.oasis.opendocument.text-master' // Document principal
			)*/ null,

			true
		);

		if ($file->er)
		{
			wd_log_error($file->er_message);

			$this->response->file = $file;

			return false;
		}

		$this->file = $file;

		return true;
	}

	protected function process()
	{
		global $core;

		$file = $this->file;
		$path = null;

		if ($file->location)
		{
			$uniqid = uniqid('', true);

			$destination = $core->config['repository.temp'] . '/' . $uniqid . $file->extension;

			$file->move($_SERVER['DOCUMENT_ROOT'] . $destination, true);
		}

		$this->terminus = true;

		return WdAttachmentsElement::create_attachment($file);
	}
}