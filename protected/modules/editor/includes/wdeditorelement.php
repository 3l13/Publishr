<?php

class WdEditorElement extends WdElement
{
	const T_STYLESHEETS = '#editor-stylesheets';

	protected $editor_base_name;

	public function __construct($tags, $dummy=null)
	{
		parent::__construct('textarea', $tags);
	}

	static public function toContents($params)
	{
		return $params['contents'];
	}

	public function export()
	{

	}

	static public function render($contents)
	{
		return $contents;
	}
}