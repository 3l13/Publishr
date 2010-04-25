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
		if ($this->isAdmin())
		{
			return;
		}

		return self::model('user.roles')->load($this->rid);
	}

	/**
	 * Whether the user is the admin
	 * @return boolean
	 */

	public function isAdmin()
	{
		return ($this->uid == 1);
	}

	/**
	 * Whether the user is a guest
	 * @return boolean
	 */

	public function isGuest()
	{
		return ($this->uid == 0);
	}

	public function hasPermission($access, $module=null)
	{
		if ($this->isAdmin())
		{
			return PERMISSION_ADMINISTER;
		}

		return $this->role->hasPermission($access, $module);
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

	public function hasOwnership($module, $entry)
	{
		$permission = $this->hasPermission(PERMISSION_MAINTAIN, $module);

		if ($permission == PERMISSION_ADMINISTER)
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
			return $permission == PERMISSION_ADMINISTER;
		}

		if (!$permission || $entry->uid != $this->uid)
		{
			return false;
		}

		return true;
	}
}