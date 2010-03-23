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

		$entries = self::model('site.pages')->loadAll
		(
			'WHERE is_online = 1 AND nid IN(' . implode(',', $ids) . ')'
		);

		$entries_by_nid = array();

		foreach ($entries as $entry)
		{
			$entries_by_nid[$entry->nid] = $entry;
		}

		$nodes = array();

		foreach ($ids as $nid)
		{
			if (empty($entries_by_nid[$nid]))
			{
				continue;
			}

			$nodes[] = $entries_by_nid[$nid];
		}

		return $nodes;
	}
}