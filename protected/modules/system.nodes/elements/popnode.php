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
	const T_CONSTRUCTOR = '#popnode-constructor';
	const T_PLACEHOLDER = '#popnode-placeholder';

	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				self::T_CONSTRUCTOR => 'system.nodes',
				self::T_PLACEHOLDER => 'Sélectionner une entrée',

				'class' => 'wd-popnode button'
			)
		);

		global $document;

		$document->css->add('popnode.css');
		$document->js->add('popnode.js');
	}

	protected function getMarkup()
	{
		$this->dataset['constructor'] = $this->get(self::T_CONSTRUCTOR);
		$this->dataset['placeholder'] = $this->get(self::T_PLACEHOLDER);

		return parent::getMarkup();
	}

	protected function getInnerHTML()
	{
		$rc = parent::getInnerHTML();

		#
		#
		#

		$module = $this->get(self::T_CONSTRUCTOR);
		$value = $this->get('value', 0);
		$entry = null;

		if ($value)
		{
			global $core;

			$model = $core->models[$module];
			$entry = is_numeric($value) ? $model[$value] : $this->getEntry($model, $value);
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

		return $rc;
	}

	protected function getEntry($model, $value)
	{
		return $model->where('title = ? OR slug = ?', $value, $value)->order('created DESC')->limit(1)->one;
	}

	protected function getPreview($entry)
	{
		$title = $this->get(self::T_PLACEHOLDER);

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
}