<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Unlocks login locked after multiple failed login attempts.
 *
 * - username (string) Username of the locked account.
 * - token (string) Token to unlock the account.
 * - continue (string)[optional] Destination of the operation successful process. Default to '/'.
 */
class user_users__unlock_login_WdOperation extends WdOperation
{
	protected function __get_record()
	{
		$username = $this->params['username'];

		return $this->module->model->where('username = ? OR email = ?', $username, $username)->one;
	}

	protected function validate()
	{
		global $core;

		if (empty($this->params['username']) || empty($this->params['token']))
		{
			return false;
		}

		$user = $this->record;

		if (!$user)
		{
			throw new WdHTTPException('Unknown user', array(), 404);
		}

		$token = $this->params['token'];

		if ($user->metas['login_unlock_token'] != base64_encode(WdSecurity::pbkdf2($token, $core->configs['user']['unlock_login_salt'])))
		{
			throw new WdHTTPException('Invalid token.', array());
		}

		return true;
	}

	protected function process()
	{
		global $core;

		$user = $this->record;

		$user->metas['login_unlock_token'] = null;
		$user->metas['login_unlock_time'] = null;
		$user->metas['failed_login_count'] = 0;

		wd_log_done('Login has been unlocked');

		$this->location = isset($this->params['continue']) ? $this->params['continue'] : '/';

		return true;
	}
}