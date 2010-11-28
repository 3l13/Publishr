<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

//throw new WdException('The class is using auto-config !');

class widgets_WdEditorElement extends WdEditorElement
{
	static protected $config = array();

	static public function __static_construct()
	{
		self::$config = Wdconfig::get_constructed('widgets', 'merge');
	}

	static public function toContents($params)
	{
		if (empty($params['contents']))
		{
			return;
		}

		return json_encode(array_keys($params['contents']));
	}

	static public function render($contents)
	{
		$selected = json_decode($contents);

		if ($contents && !$selected)
		{
			throw new WdException('Unable to decode contents: !contents', array('!contents' => $contents));
		}

		$selected = array_flip($selected);
		$availables = self::$config;

		$undefined = array_diff_key($selected, $availables);

		if ($undefined)
		{
			throw new WdException('Undefined widget(s): :list', array(':list' => implode(', ', array_keys($undefined))));
		}

		$list = array_intersect_key(self::$config, $selected);

		if (!$list)
		{
			return;
		}

		$list = array_merge($selected, $list);

		$rc = '';

		foreach ($list as $id => $widget)
		{
			$rc .= self::renderWidget($widget);
		}

		return $rc;
	}

	static protected function renderWidget($widget)
	{
		global $core, $publisher;

		if (isset($widget['file']))
		{
			$file = $widget['file'];

			if (substr($file, -4, 4) == '.php')
			{
				ob_start();

				require $file;

				return ob_get_clean();
			}
			else if (substr($file, -5, 5) == '.html')
			{
				return Patron(file_get_contents($file), null, array('file' => $file));
			}
			else
			{
				throw new WdException('Unable to process file %file, unsupported type', array('%file' => $file));
			}
		}
		else if (isset($widget['module']) && isset($widget['block']))
		{
			return $core->getModule($widget['module'])->getBlock($widget['block']);
		}
		else
		{
			throw new WdException('Unable to render view %view. The description of the view is invalid', array('%view' => $widget));
		}
	}


	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'ul', $tags + array
			(
				'class' => 'widgets-selector'
			)
		);

		if ($this->get(self::T_DESCRIPTION) === null)
		{
			$this->set
			(
				self::T_DESCRIPTION, "Sélectionner les widgets à afficher. Vous pouvez
				les ordonner par glissé-déposé."
			);
		}

		global $document;

		$document->css->add('../public/widgets.css');
		$document->js->add('../public/widgets.js');
	}

	public function getInnerHTML()
	{
		$rc = parent::getInnerHTML();

		$value = $this->get('value');
		$name = $this->get('name');

		$value = json_decode($value);
		$value = is_array($value) ? array_flip($value) : array();

		// TODO-20100204: check deprecated widgets ids

		$list = array_merge($value, self::$config);

		//wd_log('value: \1, list: \2 \3', array($value, $list, array_merge($value, $list)));

		foreach ($list as $id => $widget)
		{
			$rc .= '<li>';

			$rc .= new WdElement
			(
				WdElement::E_CHECKBOX, array
				(
					WdElement::T_LABEL => $widget['title'],

					'name' => $name . '[' . $id . ']',
					'checked' => isset($value[$id])
				)
			);

			$rc .= '</li>';
		}

		return $rc;
	}
}