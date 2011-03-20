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
 * Includes a record is the home page.
 */
class contents__home_include_WdOperation extends WdOperation
{
	/**
	 * Controls for the operation: permission(maintain), record and ownership.
	 *
	 * @see WdOperation::__get_controls()
	 */
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_PERMISSION => WdModule::PERMISSION_MAINTAIN,
			self::CONTROL_RECORD => true,
			self::CONTROL_OWNERSHIP => true
		)

		+ parent::__get_controls();
	}

	protected function validate()
	{
		return true;
	}

	protected function process()
	{
		$record = $this->record;
		$record->is_home_excluded = false;
		$record->save();

		wd_log_done('%title is now included in the home page', array('%title' => $record->title));

		return true;
	}
}