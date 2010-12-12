<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPopImageElement extends WdPopNodeElement
{
	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			$tags + array
			(
				self::T_CONSTRUCTOR => 'resources.images',
				self::T_PLACEHOLDER => 'Sélectionner une image',

				'class' => 'wd-popnode wd-popimage button'
			)
		);

		global $document;

		$document->css->add('popimage.css');
	}

	protected function getEntry($model, $value)
	{
		return $model->where('path = ? OR title = ? OR slug = ?', $value, $value, $value)->order('created DESC')->limit(1)->one;
	}

	protected function getPreview($entry)
	{
		$src = null;

		if ($entry)
		{
			$value = $entry->nid;

			$src = WdOperation::encode
			(
				'thumbnailer', 'get', array
				(
					'src' => $entry->path,
					'w' => 64,
					'h' => 64,
					'method' => 'surface'
				)
			);

			$title = $entry->title;
		}

		$rc = '<div class="preview">' . new WdElement
		(
			'img', array
			(
				'src' => $src,
				'alt' => ''
			)
		)

		. '</div>';

		$rc .= parent::getPreview($entry);

		#
		#
		#

		return $rc;
	}
}