<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class system_cache__enable_WdOperation extends system_cache__ROOT_WdOperation
{
	protected function process()
	{
		$cache_id = $this->key;

		if (in_array($cache_id, self::$internal))
		{
			return $this->alter_core_config(substr($cache_id, 5), true);
		}

		return $this->{$this->callback}();
	}
}