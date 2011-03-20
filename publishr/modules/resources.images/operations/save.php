<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class resources_images__save_WdOperation extends resources_files__save_WdOperation
{
	protected $accept = array
	(
		'gif' => 'image/gif',
		'png' => 'image/png',
		'jpg' => 'image/jpeg'
	);
}