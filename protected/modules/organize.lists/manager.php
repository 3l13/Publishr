<?php

class organize_lists_WdManager extends system_nodes_WdManager
{
	protected function get_cell_title($entry, $tag)
	{
		global $core;

		$titles = $core->getModule('system.nodes')->model()->select
		(
			'title', 'INNER JOIN {prefix}organize_lists_nodes AS jn ON nodeid = nid
			WHERE listid = ? ORDER BY jn.weight', array
			(
				$entry->nid
			)
		)
		->fetchAll(PDO::FETCH_COLUMN);

		if ($titles)
		{
			$last = array_pop($titles);

			$includes = $titles
				? t('Comprenant&nbsp;: !list et !last', array('!list' => wd_shorten(implode(', ', $titles), 128, 1), '!last' => $last))
				: t('Comprenant&nbsp;: !entry', array('!entry' => $last));
		}
		else
		{
			$includes = '<em>La liste est vide</em>';
		}

		$title  = parent::get_cell_title($entry, $tag) . ' <span class="small">(' . $entry->slug . ')</span>';
		$title .= '<br />';
		$title .= '<span class="small">';
		$title .= $includes;
		$title .= '</span>';

		return $title;
	}
}