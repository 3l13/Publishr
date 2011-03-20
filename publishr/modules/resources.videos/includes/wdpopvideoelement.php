<?php

class WdPopVideoElement extends WdPopNodeWidget
{
	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			$tags + array
			(
				self::T_CONSTRUCTOR => 'resources.videos',
				self::T_PLACEHOLDER => 'Sélectionner une image',

				'class' => 'widget-pop-node wd-popvideo button'
			)
		);
	}

	protected function getEntry($model, $value)
	{
		return $model->where('path = ? OR title = ? OR slug = ?', $value, $value, $value)->order('created DESC')->limit(1)->one;
	}

	protected function getPreview($entry)
	{
		$rc = '';

		$src = '';

		if ($entry && $entry->poster)
		{
			$src = $entry->poster->path;
		}

		$rc .= new WdElement
		(
			'img', array
			(
				'src' => WdOperation::encode
				(
					'thumbnailer/get', array
					(
						'src' => $src,
						'w' => 64,
						'h' => 64,
						'method' => 'surface'
					)
				),

				'alt' => ''
			)
		);

		$rc .= '<br />';

		$rc .= parent::getPreview($entry);

		return $rc;
	}
}