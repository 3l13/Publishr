<?php

class system_registry_WdManager extends WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => 'name'
			)
		);
	}

	protected function columns()
	{
		return array
		(
			'name' => array
			(
			)
		);
	}

	protected function get_cell_name($entry, $tag)
	{
		$rc = self::modify_callback($entry, $tag, $this);

		$rc .= '<pre>' . wd_entities($entry->value) . '</pre>';

		return $rc;
	}
}