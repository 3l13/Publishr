<?php

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

			$this->setTag(self::T_OPTIONS, $tree);
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}

		return parent::__toString();
	}
}