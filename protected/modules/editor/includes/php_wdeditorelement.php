<?php

class php_WdEditorElement extends WdEditorElement
{
	static public function render($contents)
	{
		global $core, $publisher, $user;

		ob_start();

		eval('?>' . $contents);

		$contents = ob_get_contents();

		ob_end_clean();

		return $contents;
	}

	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			$tags + array
			(
				'class' => 'editor code php'
			)
		);

		global $document;

		$document->addStyleSheet('../public/code.css');
	}
}