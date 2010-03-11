<?php

class WdPopImageElement extends WdElement
{
	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				'class' => 'wd-popimage button'
			)
		);

		global $document;

		$document->addStyleSheet('../public/wdpopimage.css');
		$document->addJavascript('../public/wdpopimage.js');
	}

	protected function getInnerHTML()
	{
		$rc = parent::getInnerHTML();

		#
		#
		#

		$value = $this->getTag('value', 0);
		$entry = null;

		if ($value)
		{
			global $core;

			$model = $core->getModule('resources.images')->model();

			if (!is_numeric($value))
			{
				$entry = $model->loadRange
				(
					0, 1, 'WHERE (path = ? OR title = ? OR slug = ?) ORDER BY created DESC', array
					(
						$value, $value, $value
					)
				)
				->fetchAndClose();
			}
			else
			{
				$entry = $model->load($value);
			}
		}

		$src = '';
		$title = "Aucune image sélectionnée";

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

		$rc .= new WdElement
		(
			'img', array
			(
				'src' => $src,
				'alt' => $title
			)
		);

		#
		# input
		#

		$name = $this->getTag('name');

		if ($name)
		{
			$rc .= new WdElement
			(
				WdElement::E_HIDDEN, array
				(
					'name' => $name,
					'value' => $value
				)
			);
		}

		#
		#
		#

		return $rc;
	}
}