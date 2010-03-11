<?php

class WdMultiEditorElement extends WdElement
{
	protected $editor;
	protected $editor_name;
	protected $editor_tags;

	public function __construct($editor, $tags)
	{
		$this->editor_name = $editor ? $editor : 'moo';
		$this->editor_tags = $tags;

		parent::__construct('div', $tags);
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
		$rc  = '<div style="float: right">';
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
					//'textmark' => 'Textmark',
					'patron' => 'Patron',
					//'php' => 'PHP'
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

		$rc  = '<div class="editor-wrapper">';
		$rc .= $this->getInnerHTML();
		$rc .= '</div>';

		return $rc;
	}
}