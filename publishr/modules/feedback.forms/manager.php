<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class feedback_forms_WdManager extends system_nodes_WdManager
{
	protected function columns()
	{
		return parent::columns() + array
		(
			'modelid' => array
			(

			)
		);
	}

	static protected $modelid_models;

	protected function render_cell_modelid($record, $property)
	{
		global $core;

		if (empty(self::$modelid_models))
		{
			self::$modelid_models = $core->configs->synthesize('formmodels', 'merge');
		}

		$modelid = $record->$property;
		$label = $modelid;

		if (isset(self::$modelid_models[$modelid]))
		{
			$label = $this->t->__invoke(self::$modelid_models[$modelid]['title']);
		}

		return $this->render_filter_cell($record, $property, $label);
	}
}