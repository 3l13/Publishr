<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdMultiEditorElement extends WdElement
{
	const T_NOT_SWAPPABLE = '#meditor-not-wappable';
	/*
	const T_BINDABLE = '#meditor-bindable';
	const T_BIND_TARGET = '#meditor-bind-target';
	*/

	protected $editor;
	protected $editor_name;
	protected $editor_tags;

	public function __construct($editor, $tags)
	{
		$this->editor_name = $editor ? $editor : 'moo';
		$this->editor_tags = $tags;

		parent::__construct('div', $tags);

		#
		#
		#

		global $document;

		$document->css->add('../public/multi.css');
		$document->js->add('../public/multi.js');
	}

	public function set($name, $value=null)
	{
		if ($name == 'value')
		{
			if (is_array($value))
			{
				$this->editor = null;

				$this->editor_name = $value['editor'];

				$this->editor()->set('value', $value['contents']);

				return;
			}
		}

		parent::set($name, $value);
	}

	public function export()
	{
		return $this->editor()->export();
	}

	public function editor()
	{
		if (!$this->editor)
		{
			$this->editor_base_name = $this->get('name');

			$name = $this->editor_base_name . '[contents]';

			$id = strtr
			(
				$name, array
				(
					'[' => '-',
					']' => ''
				)
			);

			#
			#
			#

			$editor_class = $this->editor_name . '_WdEditorElement';

			$this->editor = new $editor_class
			(
				array
				(
					'id' => $id,
					'name' => $this->editor_base_name . '[contents]'
				)

				+ $this->tags
			);
		}

		return $this->editor;
	}

	protected function options()
	{
		$rc = '';

		/*
		#
		# bind
		#

		if ($this->get(self::T_BINDABLE))
		{
			$rc .= '<div style="float: left">';

			$rc .= new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdElement::T_LABEL => 'Fichier cible',
					WdElement::T_LABEL_POSITION => 'left',
					'name' => $this->editor_base_name . '[bind]',
					'value' => $this->get(self::T_BIND_TARGET),
					'size' => 48
				)
			);

			$rc .= '</div>';
		}
		*/

		#
		# editor selector
		#

		$rc .= '<div style="float: right">';
		$rc .= 'Éditeur&nbsp;: ';

		$rc .= new WdElement
		(
			'select', array
			(
				WdElement::T_OPTIONS => array
				(
					'raw' => 'Texte brut',
					//'html-code' => 'HTML Code',
					'moo' => 'HTML WYSIWYG',
					'textmark' => 'Textmark',
					'patron' => 'Patron',
					'php' => 'PHP',
					'view' => 'Vue',
					'widgets' => 'Gadgets'
				),

				'name' => $this->editor_base_name . '[editor]',
				'class' => 'editor-selector',
				'value' => $this->editor_name
			)
		);

		$rc .= '</div>';

		return $rc;
	}

	protected function getInnerHTML()
	{
		$rc = $this->editor();

		if ($this->get(self::T_NOT_SWAPPABLE))
		{
			$rc .= '<input type="hidden" name="' . $this->editor_base_name . '[editor]" value="' . $this->editor_name . '" />';
		}
		else
		{
			$options = $this->options();

			if ($options)
			{
				$rc .= '<div class="editor-options">';
				$rc .= $options;
				$rc .= '<div class="clear"></div>';
				$rc .= '</div>';
			}
		}

		return $rc;
	}

	public function __toString()
	{
		#
		# create editor
		#

		$rc  = '<div class="editor-wrapper"';

		$id = $this->get('id');

		if ($id)
		{
			$rc .= ' id="' . $id . '"';
		}

		$rc .= '>';
		$rc .= $this->getInnerHTML();
		$rc .= '</div>';

		return $rc;
	}
}