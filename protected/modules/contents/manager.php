<?php

class contents_WdManager extends system_nodes_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_ORDER_BY => array('date', 'desc')
			)
		);
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			'date' => array
			(
				self::COLUMN_CLASS => 'date',
				self::COLUMN_HOOK => array($this, 'get_cell_datetime')
			)
		);
	}
}