<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPopImageWidget extends WdPopNodeWidget
{
	const T_PREVIEW_WIDTH = '#preview-width';
	const T_PREVIEW_HEIGHT = '#preview-height';

	public function __construct($tags=array(), $dummy=null)
	{
		global $core;

		parent::__construct
		(
			$tags + array
			(
				self::T_PREVIEW_WIDTH => 64,
				self::T_PREVIEW_HEIGHT => 64,
				self::T_CONSTRUCTOR => 'resources.images',
				self::T_PLACEHOLDER => 'Sélectionner une image'
			)
		);

		$this->dataset = array
		(
			'adjust' => 'adjust-image',
			'preview-width' => $this->get(self::T_PREVIEW_WIDTH),
			'preview-height' => $this->get(self::T_PREVIEW_HEIGHT)
		)

		+ $this->dataset;

		$core->document->css->add('pop-image.css');
		$core->document->js->add('pop-image.js');
	}

	protected function getEntry($model, $value)
	{
		return $model->where('path = ? OR title = ? OR slug = ?', $value, $value, $value)->order('created DESC')->one;
	}

	protected function getPreview($record)
	{
		$w = $this->get(self::T_PREVIEW_WIDTH, 64);
		$h = $this->get(self::T_PREVIEW_HEIGHT, 64);

		$rc = '<div class="preview">' . new WdElement
		(
			'img', array
			(
				'src' => $record ? $record->thumbnail("w:$w;h:$h;m:surface") : null,
				'alt' => ''
			)
		)

		. '</div>';

		return $rc . parent::getPreview($record);
	}
}