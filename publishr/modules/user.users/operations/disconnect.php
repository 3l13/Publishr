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
 * Disconnects the user from the system by removing its identifier form its session.
 */
class user_users__disconnect_WdOperation extends WdOperation
{
	/**
	 * Validates the operation if the user is actually connected.
	 *
	 * @see WdOperation::validate()
	 */
	protected function validate()
	{
		global $core;

		if (!$core->user_id)
		{
			throw new WdException('You are not connected.');
		}

		return true;
	}

	/**
	 * Removes the user id form the session and set the location of the operation to the location
	 * defined by `$_GET[location]` or the HTTP referer, or '/'.
	 *
	 * @see WdOperation::process()
	 */
	protected function process()
	{
		global $core;

		unset($core->session->application['user_id']);

		$this->location = isset($_GET['location']) ? $_GET['location'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/');

		return true;
	}
}