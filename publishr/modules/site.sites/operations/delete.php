<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class site_sites__delete_WdOperation extends delete_WdOperation
{
	protected function process()
	{
		$rc = parent::process();

		$this->module->update_cache();

		return $rc;
	}
}