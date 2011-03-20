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
 * Excludes a record from the home page.
 */
class contents__home_exclude_WdOperation extends contents__home_include_WdOperation
{
	protected function process()
	{
		$record = $this->record;
		$record->is_home_excluded = true;
		$record->save();

		wd_log_done('%title is now excluded from the home page', array('%title' => $record->title));

		return true;
	}
}