<?php

class organize_lists_WdActiveRecord extends system_nodes_WdActiveRecord
{
	protected function __get_nodes()
	{
		return self::model('site.pages')->loadAll
		(
			'INNER JOIN {prefix}organize_lists_nodes AS jn ON nodeid = nid
			WHERE is_online = 1 AND listid = ? ORDER BY jn.weight', array
			(
				$this->nid
			)
		)
		->fetchAll();
	}
}