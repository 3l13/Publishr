<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPopImageElement extends WdPopNodeElement
{
	public function __construct($tags=array(), $dummy=null)
	{
		global $document;

		parent::__construct
		(
			$tags + array
			(
				self::T_CONSTRUCTOR => 'resources.images',
				self::T_PLACEHOLDER => 'Sélectionner une image',

				'class' => 'wd-popnode wd-popimage button'
			)
		);

		$this->dataset['adjust'] = 'adjustimage';

		$document->css->add('popimage.css');
	}

	protected function getEntry($model, $value)
	{
		return $model->where('path = ? OR title = ? OR slug = ?', $value, $value, $value)->order('created DESC')->limit(1)->one;
	}

	protected function getPreview($record)
	{
		$rc = '<div class="preview">' . new WdElement
		(
			'img', array
			(
				'src' => $record ? $record->thumbnail('w:64;h:64;m:surface') : null,
				'alt' => ''
			)
		)

		. '</div>';

		return $rc . parent::getPreview($record);
	}
}