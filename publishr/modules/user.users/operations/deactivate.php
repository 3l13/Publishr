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
 * Disables a user account.
 */
class user_users__deactivate_WdOperation extends user_users_activate_WdOperation
{
	protected function process()
	{
		$record = $this->record;
		$record->is_activated = false;
		$record->save();

		wd_log_done('!name account is deactivated.', array('!name' => $record->name));

		return true;
	}
}