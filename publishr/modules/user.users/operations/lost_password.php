<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_users__lost_password_WdOperation extends WdOperation
{
	protected function validate()
	{
		if (empty($this->params[User::EMAIL]))
		{
			wd_log_error('The field %field is required!', array('%field' => 'Votre adresse E-Mail'));

			return false;
		}

		return true;
	}

	protected function process()
	{
		$email = $this->params[User::EMAIL];
		$uid = $this->module->model->select('uid')->find_by_email($email)->rc;

		if (!$uid)
		{
			wd_log_error('Unknown E-Mail address: %email', array('%email' => $email));

			return false;
		}

		return $this->module->send_password($uid);
	}
}
