<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WdAdjustTemplateElement extends WdElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct('select', $tags);
	}

	public function __toString()
	{
		global $core;

		$list = $core->site->templates;

		if (!$list)
		{
			return '<p class="warn">There is no template available.</p>';
		}

		$options = array_combine($list, $list);

		$this->set(self::T_OPTIONS, array(null => '<auto>') + $options);

		return parent::__toString();
	}
}