<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class php_WdEditorElement extends WdEditorElement
{
	static public function render($contents)
	{
		global $core, $publisher, $app;

		ob_start();

		eval('?>' . $contents);

		return ob_get_clean();
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

		$document->css->add('../public/code.css');
	}
}