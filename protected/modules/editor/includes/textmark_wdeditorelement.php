<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class textmark_WdEditorElement extends WdEditorElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'textarea', $tags + array
			(
				'class' => 'editor textmark'
			)
		);
	}

	static public function render($contents)
	{
		return Textmark_Parser::parse($contents);
	}
}