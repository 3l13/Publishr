<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class organize_lists_WdManager extends system_nodes_WdManager
{
	protected function get_cell_title($entry, $tag)
	{
		global $core;

		$titles = $core->models['system.nodes']->select('title')
		->joins('INNER JOIN {prefix}organize_lists_nodes AS jn ON nodeid = nid')->where('listid = ?', $entry->nid)
		->order('jn.weight')
		->all(PDO::FETCH_COLUMN);

		if ($titles)
		{
			$last = array_pop($titles);

			$includes = $titles
				? t('Comprenant&nbsp;: !list et !last', array('!list' => wd_shorten(implode(', ', $titles), 80, 1), '!last' => $last))
				: t('Comprenant&nbsp;: !entry', array('!entry' => $last));
		}
		else
		{
			$includes = '<em>La liste est vide</em>';
		}

		$title  = parent::get_cell_title($entry, $tag)/* . ' <span class="small light">(' . $entry->slug . ')</span>'*/;
		$title .= '<br />';
		$title .= '<span class="small">';
		$title .= $includes;
		$title .= '</span>';

		return $title;
	}
}