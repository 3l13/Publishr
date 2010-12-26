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
	const T_EDITOR_TAGS = '#meditor-tags';
	const T_SELECTOR_NAME = '#meditor-selector-name';
	const T_NOT_SWAPPABLE = '#meditor-not-wappable';

	protected $editor;
	protected $editor_name;

	public function __construct($editor, $tags)
	{
		$this->editor_name = $editor ? $editor : 'moo';

		parent::__construct
		(
			'div', $tags + array
			(
				self::T_SELECTOR_NAME => 'editor',

				'class' => 'editor-wrapper'
			)
		);

		#
		#
		#

		global $document;

		$document->css->add('../public/multi.css');
		$document->js->add('../public/multi.js');
	}

	public function editor()
	{
		if (!$this->editor)
		{
			$editor_class = $this->editor_name . '_WdEditorElement';

			$this->editor = new $editor_class
			(
				$this->get(self::T_EDITOR_TAGS, array()) + array
				(
					WdElement::T_REQUIRED => $this->get(self::T_REQUIRED),
					WdElement::T_DEFAULT => $this->get(self::T_DEFAULT),

					'name' => $this->get('name'),
					'value' => $this->get('value')
				)
			);

			if ($this->editor->type == 'textarea')
			{
				$rows = $this->get('rows');

				if ($rows !== null)
				{
					$this->editor->set('rows', $rows);
				}
			}
		}

		return $this->editor;
	}

	protected function options()
	{
		$rc = '';

		#
		# editor selector
		#

		$rc .= '<div style="float: right">';

		$rc .= new WdElement
		(
			'select', array
			(
				WdElement::T_LABEL => 'Éditeur',
				WdElement::T_LABEL_POSITION => 'before',
				WdElement::T_OPTIONS => array
				(
					'raw' => 'Texte brut',
					//'html-code' => 'HTML Code',
					'moo' => 'HTML WYSIWYG',
					'textmark' => 'Textmark',
					'patron' => 'Patron',
					'php' => 'PHP',
					'view' => 'Vue',
					'widgets' => 'Gadgets',
					//'nodeadjust' => 'Objet'
				),

				'name' => $this->get(self::T_SELECTOR_NAME),
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
			$rc .= '<input type="hidden" name="' . $this->get(self::T_SELECTOR_NAME) .'" value="' . $this->editor_name . '" />';
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

		$this->dataset['contents-name'] = $this->get('name');
		$this->dataset['selector-name'] = $this->get(self::T_SELECTOR_NAME);

		return $rc;
	}
}