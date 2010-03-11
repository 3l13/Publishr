<?php

class WdMultiEditorElement extends WdElement
{
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

		$document->addStyleSheet('../public/multi.css');
		$document->addJavascript('../public/multi.js');
	}

	public function setTag($name, $value=null)
	{
		if ($name == 'value')
		{
			if (is_array($value))
			{
				$this->editor = null;

				$this->editor_name = $value['editor'];

				$this->editor()->setTag('value', $value['contents']);

				return;
			}
		}

		parent::setTag($name, $value);
	}

	public function export()
	{
		return $this->editor()->export();
	}

	public function editor()
	{
		if (!$this->editor)
		{
			$this->editor_base_name = $this->getTag('name');

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

		if ($this->getTag(self::T_BINDABLE))
		{
			$rc .= '<div style="float: left">';

			$rc .= new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdElement::T_LABEL => 'Fichier cible',
					WdElement::T_LABEL_POSITION => 'left',
					'name' => $this->editor_base_name . '[bind]',
					'value' => $this->getTag(self::T_BIND_TARGET),
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
		$rc = '';

		$rc .= $this->editor();

		$options = $this->options();

		if ($options)
		{
			$rc .= '<div class="editor-options">';
			$rc .= $options;
			$rc .= '<div class="clear"></div>';
			$rc .= '</div>';
		}

		return $rc;
	}

	public function __toString()
	{
		#
		# create editor
		#

		$rc  = '<div class="editor-wrapper"';

		$id = $this->getTag('id');

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