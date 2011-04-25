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

	protected function get_cell_modelid($entry, $tag)
	{
		global $core;

		if (empty(self::$modelid_models))
		{
			self::$modelid_models = $core->configs->synthesize('formmodels', 'merge');
		}

		$modelid = $entry->$tag;
		$label = $modelid;

		if (isset(self::$modelid_models[$modelid]))
		{
			$label = t(self::$modelid_models[$modelid]['title']);
		}

		return parent::select_code($tag, $entry->$tag, $label, $this);
	}
}