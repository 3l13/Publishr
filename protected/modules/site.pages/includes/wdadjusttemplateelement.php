<?php

class WdAdjustTemplateElement extends WdElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct('select', $tags);
	}

	public function __toString()
	{
		global $app;

		$list = $app->working_site->templates;
		$options = array_combine($list, $list);

		$this->set(self::T_OPTIONS, array(null => '<auto>') + $options);

		return parent::__toString();
	}
}