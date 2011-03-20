<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class site_sites__save_WdOperation extends publishr_save_WdOperation
{
	protected function process()
	{
		$rc = parent::process();

		$record = $this->module->model[$rc['key']];

		wd_log_done
		(
			$rc['mode'] == 'update' ? '%title has been updated in %module.' : '%title has been created in %module.', array
			(
				'%title' => wd_shorten($record->title), '%module' => $this->module->title
			),

			'save'
		);

		$this->module->update_cache();

		return $rc;
	}
}