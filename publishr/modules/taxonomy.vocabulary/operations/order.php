<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class taxonomy_vocabulary__order_WdOperation extends WdOperation
{
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_OWNERSHIP => true
		)

		+ parent::__get_controls();
	}

	protected function validate()
	{
		return !empty($this->params['terms']);
	}

	protected function process()
	{
		$w = 0;
		$weights = array();
		$update = $this->module->model->prepare('UPDATE {prefix}taxonomy_terms SET weight = ? WHERE vtid = ?');

		foreach ($this->params['terms'] as $vtid => $dummy)
		{
			$update->execute(array($w, $vtid));
			$weights[$vtid] = $w++;
		}
	}
}