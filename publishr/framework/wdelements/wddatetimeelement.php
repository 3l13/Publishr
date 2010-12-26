<?php

/**
 * This file is part of the WdElements framework
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdelements/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdelements/license/
 */

class WdDateTimeElement extends WdDateElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			$tags + array
			(
				'size' => 24,
				'class' => 'datetime'
			)
		);
	}
}