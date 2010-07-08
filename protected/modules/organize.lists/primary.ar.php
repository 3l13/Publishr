<?php

class organize_lists_WdActiveRecord extends system_nodes_WdActiveRecord
{
	protected function __get_nodes()
	{
		return $this->model('organize.lists/nodes')->loadAll
		(
			'WHERE listid = ? ORDER BY weight', array
			(
				$this->nid
			)
		)
		->fetchAll();
	}
}