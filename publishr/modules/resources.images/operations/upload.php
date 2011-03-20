<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Appends a preview to the response of the operation.
 *
 * @see resources_files__upload_WdOperation
 */
class resources_images__upload_WdOperation extends resources_files__upload_WdOperation
{
	protected $accept = array
	(
		'gif' => 'image/gif',
		'png' => 'image/png',
		'jpg' => 'image/jpeg'
	);

	protected function process()
	{
		$rc = parent::process();

		if ($this->response->infos)
		{
			$path = $this->file->location;

			// TODO-20110106: compute surface w & h and use them for img in order to avoid poping

			$this->response->infos = '<div class="preview">'

			.

			new WdElement
			(
				'img', array
				(
					'src' => WdOperation::encode
					(
						'thumbnailer/get', array
						(
							'src' => $path,
							'w' => 64,
							'h' => 64,
							'format' => 'png',
							'background' => 'silver,white,medium',
							'm' => 'surface',
							'uniqid' => uniqid()
						)
					),

					'alt' => ''
				)
			)

			. '</div>' . $this->response->infos;
		}

		return $rc;
	}
}