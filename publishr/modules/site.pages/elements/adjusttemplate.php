<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
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

		$list = $core->working_site->templates;

		if (!$list)
		{
			return '<p class="warn">There is no template available.</p>';
		}

		$options = array_combine($list, $list);

		$this->set(self::T_OPTIONS, array(null => '<auto>') + $options);

		return parent::__toString();
	}
}