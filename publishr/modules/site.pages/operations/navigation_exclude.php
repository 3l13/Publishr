<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class site_pages__navigation_exclude_WdOperation extends site_pages__navigation_include_WdOperation
{
	protected function process()
	{
		$record = $this->record;
		$record->is_navigation_excluded = true;
		$record->save();

		return true;
	}
}