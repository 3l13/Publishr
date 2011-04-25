<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WdPopNodeWidget extends WdWidget
{
	const T_CONSTRUCTOR = '#popnode-constructor';
	const T_PLACEHOLDER = '#popnode-placeholder';

	public function __construct($tags=array(), $dummy=null)
	{
		global $core;

		parent::__construct
		(
			'div', $tags + array
			(
				self::T_CONSTRUCTOR => 'system.nodes',
				self::T_PLACEHOLDER => 'Sélectionner un enregistrement',

				'class' => 'button'
			)
		);

		$this->dataset['adjust'] = 'adjust-node';

		$document = $core->document;

		$document->css->add('pop-node.css');
		$document->js->add('pop-node.js');
	}

	protected function getMarkup()
	{
		$this->dataset['constructor'] = $this->get(self::T_CONSTRUCTOR);
		$this->dataset['placeholder'] = $this->get(self::T_PLACEHOLDER);

		return parent::getMarkup();
	}

	protected function getInnerHTML()
	{
		global $core;

		$rc = parent::getInnerHTML();

		$constructor = $this->get(self::T_CONSTRUCTOR);
		$value = $this->get('value', 0);
		$entry = null;

		if ($value)
		{
			$model = $core->models[$constructor];

			try
			{
				$entry = is_numeric($value) ? $model[$value] : $this->getEntry($model, $value);
			}
			catch (Exception $e)
			{
				wd_log_error('PopNode: Missing record %nid', array('%nid' => $value));
			}
		}

		if (!$entry)
		{
			$this->addClass('empty');
			$value = null;
		}

		$rc .= $this->getPreview($entry);

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
		return $model->where('title = ? OR slug = ?', $value, $value)->order('created DESC')->one;
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