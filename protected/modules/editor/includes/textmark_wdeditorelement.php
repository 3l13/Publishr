<?php

class textmark_WdEditorElement extends WdEditorElement
{
	static public function render($contents)
	{
		require_once WDPATRON_ROOT . 'includes/textmark.php';

		return Markdown($contents);
	}
}