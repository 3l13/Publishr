<?php

class WdPopVideoElement extends WdPopNodeElement
{
	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			$tags + array
			(
				self::T_SCOPE => 'resources.videos',
				self::T_EMPTY_LABEL => 'Aucune vidéo sélectionnée',

				'class' => 'wd-popnode wd-popvideo button'
			)
		);
	}

	protected function getEntry($model, $value)
	{
		return $model->loadRange
		(
			0, 1, 'WHERE (path = ? OR title = ? OR slug = ?) ORDER BY created DESC', array
			(
				$value, $value, $value
			)
		)
		->fetchAndClose();
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
					'thumbnailer', 'get', array
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