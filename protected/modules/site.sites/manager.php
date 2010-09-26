<?php

class site_sites_WdManager extends WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => 'siteid',
				self::T_ORDER_BY => 'title'
			)
		);
	}

	protected function columns()
	{
		return array
		(
			'title' => array
			(

			)
		);
	}

	protected function loadRange($offset, $limit, array $where, $order, array $params)
	{
		unset($where['siteid']);

		return parent::loadRange($offset, $limit, $where, $order, $params);
	}

	protected function get_cell_title($entry, $tag)
	{
		return parent::modify_callback($entry, $tag, $this);
	}
}