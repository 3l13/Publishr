<?php

class patron_WdEditorElement extends WdEditorElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct($tags);

		$this->addClass('patron');

		global $document;

		$document->addStyleSheet('../public/patron.css');
	}

	static public function render($contents)
	{
		// TODO-20100113: currently, the same patron object is used, but that might change. We
		// shoudl check for a `publisher` variable, and if it's not defined use the Patron function.

		return Patron($contents);
	}
}