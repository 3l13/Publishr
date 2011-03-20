<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class system_nodes__delete_WdOperation extends delete_WdOperation
{
	/**
	 * Overrides the method to create a nicer log entry.
	 *
	 * @see delete_WdOperation::process()
	 */
	protected function process()
	{
		$title = $this->record->title;
		$rc = parent::process();

		if ($rc)
		{
			wd_log_done
			(
				'%title has been deleted from %module.', array
				(
					'%title' => wd_shorten($title), '%module' => $this->module->title
				),

				'delete'
			);
		}

		return $rc;
	}
}