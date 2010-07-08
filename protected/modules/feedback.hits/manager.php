<?php

class feedback_hits_WdManager extends WdManager
{
	protected function columns()
	{
		return array
		(
			'name' => array
			(
				self::COLUMN_LABEL => 'Name'
			),

			'hits' => array
			(
				self::COLUMN_LABEL => 'Count',
				self::COLUMN_CLASS => 'size'
			),

			'first' => array
			(
				self::COLUMN_LABEL => 'First',
				self::COLUMN_CLASS => 'date',
				self::COLUMN_HOOK => array($this, 'get_cell_datetime')
			),

			'last' => array
			(
				self::COLUMN_LABEL => 'Last',
				self::COLUMN_CLASS => 'date',
				self::COLUMN_SORT => WdResume::ORDER_DESC,
				self::COLUMN_HOOK => array($this, 'get_cell_datetime')
			)
		);
	}

	protected function get_cell_name($entry, $tag)
	{
		global $core;

		$node = $core->getModule('system.nodes')->model()->load($entry->nid);

		if (!$node)
		{
			return;
		}

		$name = $node->title;

		if (!$name)
		{
			$name = '<em>' . $entry->resource . '</em>';
		}

		return wd_entities($name);
	}
}