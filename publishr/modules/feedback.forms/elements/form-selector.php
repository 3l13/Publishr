<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WdFormSelectorElement extends WdElement
{
	public function __toString()
	{
		global $core;

		$site = $core->site;
		$value = (int) $this->get('value');

		$options = $core->models['feedback.forms']->select('nid, title')
		->where('nid = ? OR ((siteid = 0 OR siteid = ?) AND (language = "" OR language = ?))', $value, $site->siteid, $site->language)
		->order('title')
		->pairs;

		if ($this->type == 'select')
		{
			$options = array(null => '') + $options;
		}

		$this->set(self::T_OPTIONS, $options);

		return parent::__toString();
	}
}