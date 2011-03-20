<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class constructor_save_WdOperation extends publishr_save_WdOperation
{
	protected function __get_properties()
	{
		$properties = parent::__get_properties();

		$properties['constructor'] = (string) $this->module;

		return $properties;
	}
}