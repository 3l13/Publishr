<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class system_modules__deactivate_WdOperation extends WdOperation
{
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_PERMISSION => WdModule::PERMISSION_ADMINISTER
		)

		+ parent::__get_controls();
	}

	protected function validate()
	{
		return true;
	}

	protected function process()
	{
		global $core;

		$enabled = json_decode($core->vars['enabled_modules'], true);
		$enabled = $enabled ? array_flip($enabled) : array();

		foreach ((array) $this->key as $key => $dummy)
		{
			unset($enabled[$key]);
		}

		$core->vars['enabled_modules'] = json_encode(array_keys($enabled));

		$this->location = '/admin/' . (string) $this->module;

		return true;
	}
}