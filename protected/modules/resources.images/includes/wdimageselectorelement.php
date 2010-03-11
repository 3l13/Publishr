<?php

class WdImageSelectorElement extends WdElement
{
	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				WdElement::T_DESCRIPTION => '<a href="' . WdRoute::encode('/resources.images') . '">Gérer les images</a>',

				'class' => 'wd-adjustimage'
			)
		);

		global $document;

		$document->addStyleSheet('../public/slimbox.css');
		$document->addJavascript('../public/slimbox.js');

		$document->addStyleSheet('../public/wdimageselector.css');
		$document->addJavascript('../public/wdimageselector.js');
	}

	public function setTag($name, $value=null)
	{
		if ($name == 'value')
		{
			if ($value !== null && !is_numeric($value))
			{
				global $core;

				$nid = $core->getModule('resources.images')->model()->select
				(
					'nid', 'WHERE (path = ? OR title = ? OR slug = ?) ORDER BY created DESC', array
					(
						$value, $value, $value
					)
				)
				->fetchColumnAndClose();

				wd_log('evaluate back: \1 to: \2', array($value, $nid));

				$value = $nid;
			}
		}

		return parent::setTag($name, $value);
	}

	protected function getInnerHTML()
	{
		$name = $this->getTag('name');
		$value = $this->getTag('value');

		$rc = parent::getInnerHTML();



		global $core;

		$model = $core->getModule('resources.images')->model();


















		/*
		$rc .= '<div class="preview" style="float: right">';

		if ($value)
		{
			$entry = $model->load($value);

			if ($entry)
			{
				$rc .= '<div class="title" style="text-align: center; border-bottom: 1px solid #CCC; margin-bottom: .5em;">' . wd_entities($entry->title) . '</div>';
				$rc .= new WdImagePreviewElement
				(
					array
					(
						'value' => $entry->path
					)
				);
			}
		}

		$rc .= '</div>';
		*/

		$rc .= '<div class="search">';
		$rc .= $this->getSearchBlock();
		$rc .= '</div>';

		$rc .= new WdElement
		(
			WdElement::E_HIDDEN, array
			(
				'class' => 'key',
				'name' => $name,
				'value' => $value
			)
		);

		return $rc;
	}

	protected function getSearchBlock()
	{
		global $core;

		$rc = '';

		$model = $core->getModule('resources.images')->model();
		$value = $this->getTag('value');

		$entries = $model->select
		(
			array('nid', 'title', 'path'), 'WHERE is_online = 1 ORDER BY modified DESC'
		)
		->fetchAll(PDO::FETCH_OBJ);


		//$rc .= '<input type="text" class="search" />';

		$rc .= '<ul class="results icons">';

		foreach ($entries as $entry)
		{
			$rc .= ($entry->nid == $value) ? '<li class="current selected">' : '<li>';

			$rc .= new WdElement
			(
				'a', array
				(
					WdElement::T_CHILDREN => array
					(
						new WdElement
						(
							'img', array
							(
								'alt' => '',
								'src' => WdOperation::encode
								(
									'thumbnailer', 'get', array
									(
										'src' => $entry->path,
										'w' => 48,
										'h' => 48,
										'format' => 'png',
										'method' => 'constrained'
									),

									true
								)
							)
						)
					),

					'href' => $entry->path . '#' . $entry->nid,
					'title' => $entry->title
				)
			);

			$rc .= '</li>';
		}

		$rc .= '</ul>';

		return $rc;
	}
}