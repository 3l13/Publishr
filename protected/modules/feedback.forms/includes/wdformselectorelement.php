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

		$options = $core->models['feedback.forms']->select
		(
			array('nid', 'title'), 'WHERE (siteid = 0 OR siteid = ?) AND (language = "" OR language = ?) ORDER BY title', array
			(
				$core->working_site_id, WdI18n::$native
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