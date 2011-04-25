<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_roles__permissions_WdOperation extends WdOperation
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

		$params = $this->params;
		$model = $this->module->model;

		foreach ($params['roles'] as $rid => $perms)
		{
			$role = $model[$rid];

			$p = array();

			foreach ($perms as $perm => $name)
			{
				if ($name == 'inherit')
				{
					continue;
				}

				if ($name == 'on')
				{
					if (isset($core->modules->descriptors[$perm]))
					{
						#
						# the module defines his permission level
						#

						$p[$perm] = $core->modules->descriptors[$perm][WdModule::T_PERMISSION];

						continue;
					}
					else
					{
						#
						# this is a special permission
						#

						$p[$perm] = true;

						continue;
					}
				}

				$p[$perm] = is_numeric($name) ? $name :  user_roles_WdActiveRecord::$permission_levels[$name];
			}

			$role->perms = json_encode($p);
			$role->save();
		}

		wd_log_done('Permissions has been saved.');

		return true;
	}
}