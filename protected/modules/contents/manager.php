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
				self::COLUMN_LABEL => 'Date',
				self::COLUMN_CLASS => 'date'
			)
		);
	}

	/*
	protected function __get_date($entry, $tag)
	{
		return parent::date_callback($entry, $tag, $this);
	}
	*/
}