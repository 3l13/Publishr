<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_users__send_password_WdOperation extends WdOperation
{
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_PERMISSION => WdModule::PERMISSION_MANAGE,
			self::CONTROL_RECORD => true
		)

		+ parent::__get_controls();
	}

	protected function validate()
	{
		return true;
	}

	protected function process()
	{
		return $this->module->send_password($this->key);
	}
}