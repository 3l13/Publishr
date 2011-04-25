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
 * The "nonce-login" operation is used to login a user using a one time, time limited pass created
 * by the "nonce-request" operation.
 */
class user_users__nonce_login_WdOperation extends WdOperation
{
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_RECORD => true
		)

		+ parent::__get_controls();
	}

	protected function __get_record()
	{
		global $core;

		return isset($this->params['email']) ? $core->models['user.users']->find_by_email($this->params['email'])->one : null;
	}

	protected function validate()
	{
		global $core;

		$params = $this->params;

		if (empty($params['token']))
		{
			return false;
		}

		$user = $this->record;

		$now = time();
		$expires = $user->metas['nonce_login.expires'];

		if ($expires < $now)
		{
			throw new WdHTTPException('The nonce login has expired');
		}

		$token = $params['token'];

		if ($user->metas['nonce_login.token'] != base64_encode(WdSecurity::pbkdf2($token, $core->configs['user']['nonce_login_salt'])))
		{
			throw new WdHTTPException('Invalid token');
		}

		$ip = $_SERVER['REMOTE_ADDR'];

		if ($ip != $user->metas['nonce_login.ip'])
		{
			throw new WdHTTPException('Invalid remote address');
		}

		return true;
	}

	protected function process()
	{
		$user = $this->record;

		$user->metas['nonce_login.expires'] = null;
		$user->metas['nonce_login.token'] = null;
		$user->metas['nonce_login.ip'] = null;

		$user->login();

		$this->location = $user->url('profile');

		return true;
	}
}