<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

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

		$this->set(self::T_OPTIONS, $options);

		return parent::__toString();
	}
}