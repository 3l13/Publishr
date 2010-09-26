<?php

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
		if (empty(self::$modelid_models))
		{
			self::$modelid_models = WdConfig::get_constructed('formmodels', 'merge');
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