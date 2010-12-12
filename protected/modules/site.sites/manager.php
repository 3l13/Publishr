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

	protected function loadRange($offset, $limit, array $conditions, $order, array $conditions_args)
	{
		unset($conditions['siteid']);

		return parent::loadRange($offset, $limit, $conditions, $order, $conditions_args);
	}

	protected function get_cell_title($entry, $tag)
	{
		return parent::modify_callback($entry, $tag, $this);
	}

	protected function get_cell_language($entry, $tag)
	{
		return WdI18n::$conventions['languages'][$entry->$tag];
	}
}