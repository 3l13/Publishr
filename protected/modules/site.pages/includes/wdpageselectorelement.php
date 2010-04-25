<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPageSelectorElement extends WdElement
{
	public function __construct($type, $tags=array())
	{
		parent::__construct($type, $tags);
	}

	public function __toString()
	{
		global $core;

		try
		{
			$module = $core->getModule('site.pages');

			$tree = $module->getTree();
			$tree = array(null => '') + $module->flattenTree($tree);

			$this->set(self::T_OPTIONS, $tree);
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}

		return parent::__toString();
	}
}