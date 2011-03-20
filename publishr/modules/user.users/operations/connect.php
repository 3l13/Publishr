<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_users__connect_WdOperation extends WdOperation
{
	/**
	 * Adds form control.
	 *
	 * @see WdOperation::__get_controls()
	 */
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_FORM => true
		)

		+ parent::__get_controls();
	}

	/**
	 * Returns the "connect" form of the target module.
	 *
	 * @see WdOperation::__get_form()
	 */
	protected function __get_form()
	{
		return $this->module->form_connect();
	}

	protected function validate()
	{
		global $core;

		$params = &$this->params;
		$username = $params[User::USERNAME];
		$password = $params[User::PASSWORD];

		$found = $core->models['user.users']->select('uid, constructor')
		->where('(username = ? OR email = ?) AND password = md5(?)', array($username, $username, $password))
		->one(PDO::FETCH_NUM);

		$form = $this->form;

		if (!$found)
		{
			$form->log(User::PASSWORD, 'Unknown username/password combination');

			return false;
		}

		list($uid, $constructor) = $found;

		$user = $core->models[$constructor][$uid];

		if (!$user->is_admin() && !$user->is_activated)
		{
			$form->log(null, 'User %username is not activated', array('%username' => $username));

			return false;
		}

		$this->record = $user;

		return true;
	}

	/**
	 * Saves the user id in the session, sets the `user` property of the core object, updates the
	 * user's last connection date and finaly changes the operation location to the same request
	 * uri.
	 *
	 * @see WdOperation::process()
	 */
	protected function process()
	{
		global $core;

		$user = $this->record;

		$core->user = $user;
		$core->session->application['user_id'] = $user->uid;
		$core->models['user.users']->execute
		(
			'UPDATE {self} SET lastconnection = now() WHERE uid = ?', array
			(
				$user->uid
			)
		);

		$this->location = $_SERVER['REQUEST_URI'];

		return true;
	}
}