<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class user_users_WdActiveRecord extends WdActiveRecord
{
	const UID = 'uid';
	const RID = 'rid';
	const EMAIL = 'email';
	const PASSWORD = 'password';
	const USERNAME = 'username';
	const FIRSTNAME = 'firstname';
	const LASTNAME = 'lastname';
	const DISPLAY = 'display';
	const CREATED = 'created';
	const LASTCONNECTION = 'lastconnection';
	const CONSTRUCTOR = 'constructor';
	const LANGUAGE = 'language';
	const IS_ACTIVATED = 'is_activated';

	public $uid;
	public $rid = 1;

	public function __construct()
	{
		unset($this->password);

		parent::__construct();
	}

	protected function model($name='user.users')
	{
		return parent::model($name);
	}

	protected function __get_name()
	{
		$values = array
		(
			$this->username,
			$this->firstname,
			$this->lastname,
			$this->firstname . ' ' . $this->lastname,
			$this->lastname . ' ' . $this->firstname
		);

		$rc = isset($values[$this->display]) ? $values[$this->display] : null;

		if (!trim($rc))
		{
			$rc = $this->username;
		}

		return $rc;
	}

	protected function __get_role()
	{
		$permissions = array();

		foreach ($this->roles as $role)
		{
			foreach ($role->levels as $access => $permission)
			{
				$permissions[$access] = $permission;
			}
		}

		$role = new Role();
		$role->levels = $permissions;

		return $role;
	}

	protected function __get_roles()
	{
		global $core;

		$model = $core->models['user.roles'];

		if (!$this->uid)
		{
			return array
			(
				$model[1]
			);
		}

		$rids = explode(',', $this->rid);

		if (!in_array(2, $rids))
		{
			array_unshift($rids, 2);
		}

		$roles = array();

		$model = $core->models['user.roles'];

		foreach ($rids as $rid)
		{
			$roles[] = $model[$rid];
		}

		return $roles;
	}

	/**
	 * Whether the user is the admin
	 * @return boolean
	 */

	public function is_admin()
	{
		return ($this->uid == 1);
	}

	/**
	 * Whether the user is a guest
	 * @return boolean
	 */

	public function is_guest()
	{
		return ($this->uid == 0);
	}

	public function has_permission($access, $module=null)
	{
		if ($this->is_admin())
		{
			return WdModule::PERMISSION_ADMINISTER;
		}

		$rc = $this->role->has_permission($access, $module);

//		echo "access: $access, module: $module, rc: $rc<br />";

		return $rc;

		/*
		foreach ($this->roles as $role)
		{
			$permission = $role->has_permission($access, $module);

//			WdDebug::trigger("$access, $module: $permission");

//			echo t("$access, $module: $permission<br />");

			if (!$permission)
			{
				continue;
			}

			return $permission;
		}

		$this->role;

		echo "user ($this->uid) has no permission ($access) for module $module<br />";

		var_dump($this->roles);
		*/
	}

	/**
	 * Checks if the user has the ownership the an entry.
	 *
	 * If the ownership information is missing from the entry (the 'uid' property is null), the user
	 * must have the ADMINISTER level to be considered the owner.
	 *
	 * @param $module
	 * @param $entry
	 * @return boolean
	 */

	public function has_ownership($module, $entry)
	{
		$permission = $this->has_permission(WdModule::PERMISSION_MAINTAIN, $module);

		if ($permission == WdModule::PERMISSION_ADMINISTER)
		{
			return true;
		}

		if (is_array($entry))
		{
			$entry = (object) $entry;
		}

		if (!is_object($entry))
		{
			throw new WdException('%var must be an object', array('%var' => 'entry'));
		}

		if (empty($entry->uid))
		{
			return $permission == WdModule::PERMISSION_ADMINISTER;
		}

		if (!$permission || $entry->uid != $this->uid)
		{
			return false;
		}

		return true;
	}
}