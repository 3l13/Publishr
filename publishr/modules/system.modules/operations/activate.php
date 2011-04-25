<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class system_modules__activate_WdOperation extends WdOperation
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
			try
			{
				$core->modules[$key] = true;
				$module = $core->modules[$key];

				if (!$module->is_installed())
				{
					$module->install();
				}

				$enabled[$key] = true;
			}
			catch (Exception $e)
			{
				wd_log_error($e->getMessage());
			}
		}

		$core->vars['enabled_modules'] = json_encode(array_keys($enabled));

		$this->location = $core->contextualize_api_string('/admin/' . (string) $this->module);

		return true;
	}
}