<?php

class user_roles_WdActiveRecord extends WdActiveRecord
{
	const RID = 'rid';
	const ROLE = 'role';
	const PERMS = 'perms';

	static public $permission_levels = array
	(
		'none' => PERMISSION_NONE,
		'access' => PERMISSION_ACCESS,
		'create' => PERMISSION_CREATE,
		'maintain' => PERMISSION_MAINTAIN,
		'manage' => PERMISSION_MANAGE,
		'administer' => PERMISSION_ADMINISTER
	);

	public $role;
	public $perms;

	protected function __get_levels()
	{
		$perms = $this->perms;

		if (!$perms)
		{
			return array();
		}

		// FIXME: remove the transition support: unserialize

		return (substr($perms, 0, 2) == 'a:') ? unserialize($perms) : (array) json_decode($perms);
	}

	public function has_permission($access, $module=null)
	{
//		wd_log('has permission ? access: <em>\1</em>, module: <em>\2</em>', $access, (string) $module);

		#
		# check 'as is' for permissions like 'modify own module';
		#

		if (is_string($access))
		{
			if (isset($this->levels[$access]))
			{
				return true;
			}

			if (isset(self::$permission_levels[$access]))
			{
				$access = self::$permission_levels[$access];
			}
			else
			{
				#
				# the special permission is not defined in our permission
				# and since it's not a standard permission level we can
				# return false
				#

				return false;
			}
		}

		#
		# check modules based permission level
		#

		if (is_object($module))
		{
			$module = (string) $module;
		}
		
		if (isset($this->levels[$module]))
		{
			$level = $this->levels[$module];

			if ($level >= $access)
			{
				#
				# we return the real permission level, not 'true'
				#

				return $level;
			}
		}

		#
		# if the permission level was not defined in the module scope
		# we check the global scope
		#

		else if (isset($this->levels['all']))
		{
			$level = $this->levels['all'];

			if ($level >= $access)
			{
				#
				# we return the real permission level, not 'true'
				#

				return $level;
			}
		}

		return false;
	}
}