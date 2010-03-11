<?php

class taxonomy_playlists_WdManager extends system_nodes_WdManager
{
	protected function get_cell_title($entry, $tag)
	{
		$title = parent::get_cell_title($entry, $tag);

		$title .= '<br />';

		global $core;

		$titles = $core->getModule('resources.songs')->model()->select
		(
			'title', 'INNER JOIN {prefix}taxonomy_playlists_songs ON songid = nid
			WHERE plid = ? ORDER BY weight', array
			(
				$entry->nid
			)
		)
		->fetchAll(PDO::FETCH_COLUMN);

		$count = count($titles);

		$title .= '<span class="small">';

		if ($count)
		{
			$title .= 'Comprenant&nbsp;: ';
			$title .= wd_excerpt(implode(', ', $titles), 32);
		}
		else
		{
			$title .= '<em>La liste de lecture est vide</em>';
		}

		$title .= '</span>';


		return $title;
	}
}