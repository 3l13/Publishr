<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
	const TIMEZONE = 'timezone';
	const IS_ACTIVATED = 'is_activated';

	public $uid;
	public $rid = 1;
	public $email;
	public $password;
	public $username;
	public $firstname;
	public $lastname;
	public $display;
	public $created;
	public $lastconnection;
	public $constructor;
	public $language;
	public $timezone;
	public $is_activated;

	protected $password_hash;

	public function __construct()
	{
		$this->password_hash = $this->password;
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

		$rids = $this->rid ? explode(',', $this->rid): array();

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

	public function has_permission($access, $target=null)
	{
		if ($this->is_admin())
		{
			return WdModule::PERMISSION_ADMINISTER;
		}

		return $this->role->has_permission($access, $target);
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

	static public function hash_password($password)
	{
		global $core;

		return sha1(WdSecurity::pbkdf2($password, $core->configs['user']['password_salt']));
	}

	/**
	 * Compare a password to the user password.
	 *
	 * @param string $password
	 * @return bool true if the password match the password hash, false otherwise.
	 */
	public function is_password($password)
	{
		return $this->password_hash === self::hash_password($password);
	}

	/**
	 * Login the user.
	 *
	 * The following things happen when the user is logged in:
	 *
	 * - The `$core->user` property is set to the user.
	 * - The `$core->user_id` property is set to the user id.
	 * - The session id is regenerated and the user id and user agent are stored in the session.
	 * - The `lastconnection` of the user is updated.
	 *
	 * @return bool true if the login is successful.
	 */
	public function login()
	{
		global $core;

		$core->user = $this;
		$core->user_id = $this->uid;
		$core->session->regenerate_id();
		$core->session->application['user_id'] = $this->uid;
		$core->session->application['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? md5($_SERVER['HTTP_USER_AGENT']) : null;

		$core->models['user.users']->execute
		(
			'UPDATE {self} SET lastconnection = now() WHERE uid = ?', array
			(
				$this->uid
			)
		);

		return true;
	}

	public function url($id)
	{
		global $core;

		if ($id == 'profile')
		{
			return $core->site->path . '/admin/profile';
		}

		return '#unknown-url';
	}
}