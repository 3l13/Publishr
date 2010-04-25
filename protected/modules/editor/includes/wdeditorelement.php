<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

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