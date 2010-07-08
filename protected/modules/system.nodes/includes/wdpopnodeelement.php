<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPopNodeElement extends WdElement
{
	const T_SCOPE = '#popnode-scope';
	const T_EMPTY_LABEL = '#popnode-empty-label';

	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				self::T_SCOPE => 'system.nodes',
				self::T_EMPTY_LABEL => 'Aucune entrée sélectionnée',

				'class' => 'wd-popnode button'
			)
		);

		global $document;

		$document->css->add('../public/wdpopnode.css');
		$document->js->add('../public/wdpopnode.js');
	}

	protected function getInnerHTML()
	{
		$rc = parent::getInnerHTML();

		#
		#
		#

		$module = $this->get(self::T_SCOPE);
		$value = $this->get('value', 0);
		$entry = null;

		if ($value)
		{
			global $core;

			$model = $core->getModule($module)->model();

			$entry = is_numeric($value) ? $model->load($value) : $this->getEntry($model, $value);
		}
		else
		{
			$this->addClass('empty');
		}

		$rc .= $this->getPreview($entry);

		#
		# input
		#

		$name = $this->get('name');

		if ($name)
		{
			$rc .= new WdElement
			(
				WdElement::E_HIDDEN, array
				(
					'name' => $name,
					'value' => $value,
					'class' => 'key'
				)
			);
		}

		$rc .= '<input type="hidden" class="options" value="' . wd_entities(json_encode($this->getOptions())) . '" />';

		#
		#
		#

		return $rc;
	}

	protected function getEntry($model, $value)
	{
		return $model->loadRange
		(
			0, 1, 'WHERE (title = ? OR slug = ?) ORDER BY created DESC', array
			(
				$value, $value, $value
			)
		)
		->fetchAndClose();
	}

	protected function getPreview($entry)
	{
		$title = $this->get(self::T_EMPTY_LABEL);

		if (!$entry)
		{
			return '<span class="title"><em>' . wd_entities($title) . '</em></span>';
		}

		$value = $entry->nid;
		$title = $entry->title;

		$label = wd_shorten($title, 32, .75, $shortened);

		$rc  = '<span class="title"' . ($shortened ? ' title="' . wd_entities($title) . '"' : '') . '>';
		$rc .= wd_entities($label) . '</span>';

		return $rc;
	}

	protected function getOptions()
	{
		return array
		(
			'scope' => $this->get(self::T_SCOPE),
			'emptyLabel' => $this->get(self::T_EMPTY_LABEL)
		);
	}
}