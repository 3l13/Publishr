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
	const T_CONFIG = '#editor-config';
	const T_STYLESHEETS = '#editor-stylesheets';

	static public function to_content(array $params, $content_id, $page_id)
	{
		return isset($params['contents']) ? $params['contents'] : null;
	}

	static public function render($contents)
	{
		return $contents;
	}
}