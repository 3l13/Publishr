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

		$params = $this->params;
		$form = $this->form;
		$username = $params[User::USERNAME];
		$password = $params[User::PASSWORD];

		$user = $core->models['user.users']->where('username = ? OR email = ?', $username, $username)->one;

		if (!$user)
		{
			$form->log(User::PASSWORD, 'Unknown username/password combination.');

			return false;
		}

		$now = time();
		$login_unlock_time = $user->metas['login_unlock_time'];

		if ($login_unlock_time)
		{
			if ($login_unlock_time > $now)
			{
				throw new WdHTTPException
				(
					"The user account has been locked after multiple failed login attempts.
					An e-mail has been sent to unlock the account. Login attempts are locked until %time,
					unless you unlock the account using the email sent.", array
					(
						'%count' => $user->metas['failed_login_count'],
						'%time' => wd_format_date($login_unlock_time, 'HH:mm')
					),

					403
				);
			}

			$user->metas['login_unlock_time'] = null;
		}

		if (!$user->is_password($password))
		{
			$form->log(User::PASSWORD, 'Unknown username/password combination.');

			$user->metas['failed_login_count'] += 1;
			$user->metas['failed_login_time'] = $now;

			if ($user->metas['failed_login_count'] > 10)
			{
				$token = base64_encode(WdSecurity::generate_token(32, 'wide'));

				$user->metas['login_unlock_token'] = base64_encode(WdSecurity::pbkdf2($token, $core->configs['user']['unlock_login_salt']));
				$user->metas['login_unlock_time'] = $now + 3600;

				$url = $core->site->url . '/api/user.users/unlock_login?username=' . urlencode($username) . '&token=' . urlencode($token) . '&continue=' . urlencode($_SERVER['REQUEST_URI']);
				$ip = $_SERVER['REMOTE_ADDR'];
				$until = wd_format_date($now + 3600, 'HH:mm');

				$t = new WdTranslatorProxi(array('scope' => array(wd_normalize($user->constructor, '_'), 'connect', 'operation')));

				$mailer = new WdMailer
				(
					array
					(
						WdMailer::T_DESTINATION => $user->email,
						WdMailer::T_BCC => 'gofromiel@gofromiel.com',
						WdMailer::T_FROM => 'no-reply@publishr.com',
						WdMailer::T_SUBJECT => "Your account has been locked",
						WdMailer::T_MESSAGE => <<<EOT
You receive this message because your account has been locked.

After multiple failed login attempts your account has been locked until $until. You can use the
following link to unlock your account and try to login again:

$url

If you forgot your password, you'll be able to request a new one.

If you didn't try to login neither forgot your password, this message might be the result of an
attack attempt on the website. If you think this is the case, please contact its admin.

The remote address of the request was: $ip.
EOT
					)
				);

				$mailer->send();

				wd_log_error("Your account has been locked, a message has been sent to your e-mail address.");
			}

			return false;
		}

		if (!$user->is_admin() && !$user->is_activated)
		{
			$form->log(null, 'User %username is not activated', array('%username' => $username));

			return false;
		}

		$user->metas['failed_login_count'] = null;

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
		$this->record->login();
		$this->location = $_SERVER['REQUEST_URI'];

		return true;
	}
}