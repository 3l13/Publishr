<?php

class WdFormSelectorElement extends WdElement
{
	public function __toString()
	{
		global $core;

		$options = $core->getModule('feedback.forms')->model()->select
		(
			array('nid', 'title'), 'WHERE (language = "" OR language = ?) ORDER BY title', array
			(
				WdLocale::$native
			)
		)
		->fetchPairs();

		if ($this->type == 'select')
		{
			$options = array(null => '') + $options;
		}

		$this->setTag(self::T_OPTIONS, $options);

		return parent::__toString();
	}
}