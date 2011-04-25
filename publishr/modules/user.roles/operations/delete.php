<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_roles__delete_WdOperation extends delete_WdOperation
{
	/**
	 * Controls for the operation: permission(manage), record and ownership.
	 *
	 * @see WdOperation::__get_controls()
	 */
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_PERMISSION => WdModule::PERMISSION_ADMINISTER,
			self::CONTROL_RECORD => true
		)

		+ parent::__get_controls();
	}

	/**
	 * The visitor (1) and user (2) roles cannot be deleted.
	 *
	 * @see delete_WdOperation::validate()
	 */
	protected function validate()
	{
		if ($this->key == 1 || $this->key == 2)
		{
			wd_log_error('The <em>visitor</em> and <em>user</em> roles cannot be deleted');

			return false;
		}

		return parent::validate();
	}
}