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
 * Saves the order of the user's dashboard blocks.
 */
class dashboard__order_WdOperation extends WdOperation
{
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true
		)

		+ parent::__get_controls();
	}

	protected function validate()
	{
		return !empty($this->params['order']);
	}

	protected function process()
	{
		global $core;

		$core->user->metas['dashboard.order'] = json_encode($this->params['order']);

		return true;
	}
}