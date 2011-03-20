<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class system_nodes__query_operation_WdOperation extends query_operation_WdOperation
{
	protected function query_online()
	{
		return array
		(
			'params' => array
			(
				'keys' => $this->params['keys']
			)
		);
	}

	protected function query_offline()
	{
		return array
		(
			'params' => array
			(
				'keys' => $this->params['keys']
			)
		);
	}
}