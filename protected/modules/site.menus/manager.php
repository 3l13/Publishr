<?php

class site_menus_WdManager extends system_nodes_WdManager
{
	protected function get_cell_title($entry, $tag)
	{
		$title = parent::get_cell_title($entry, $tag) . ' <span class="small">(' . $entry->slug . ')</span>';

		$title .= '<br />';

		global $core;

		$titles = $core->getModule('site.pages')->model()->select
		(
			'title', 'INNER JOIN {prefix}site_menus_pages AS jn ON pageid = nid
			WHERE menuid = ? ORDER BY jn.weight', array
			(
				$entry->nid
			)
		)
		->fetchAll(PDO::FETCH_COLUMN);

		$count = count($titles);

		$title .= '<span class="small">';

		if ($count)
		{
			$title .= 'Comprenant&nbsp;: ' . wd_excerpt(implode(', ', $titles), 48);
		}
		else
		{
			$title .= '<em>Le menu est vide</em>';
		}

		$title .= '</span>';

		return $title;
	}
}