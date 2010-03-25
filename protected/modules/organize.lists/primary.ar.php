<?php

class organize_lists_WdActiveRecord extends system_nodes_WdActiveRecord
{
	protected function __get_nodes()
	{
		$ids = $this->model('organize.lists/nodes')->select
		(
			'nodeid', 'WHERE listid = ? ORDER BY weight', array
			(
				$this->nid
			)
		)
		->fetchAll(PDO::FETCH_COLUMN);

		if (!$ids)
		{
			return;
		}

		$entries = self::model($this->scope)->loadAll
		(
			'WHERE is_online = 1 AND nid IN(' . implode(',', $ids) . ')'
		)
		->fetchAll();

		return WdArray::reorderByProperty($entries, $ids, Node::NID);
	}
}