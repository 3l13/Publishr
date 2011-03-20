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
 * The "unlock" operation is used to unlock a record previously locked using the "lock"
 * operation.
 */
class unlock_WdOperation extends WdOperation
{
	public function __invoke()
	{
		global $core;

		$this->module = $core->modules[$this->params['module']];
		$this->key = $this->params['key'];

		return parent::__invoke();
	}

	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_PERMISSION => WdModule::PERMISSION_MAINTAIN,
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
		return $this->module->unlock_entry((int) $this->key);
	}
}