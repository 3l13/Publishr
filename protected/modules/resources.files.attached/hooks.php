<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_files_attached_WdHooks
{
	static public function get_attached_files(system_nodes_WdActiveRecord $ar)
	{
		global $core;

		$entries = $core->db()->query
		(
			'SELECT node.*, file.*, IF(attached.title != "", attached.title, node.title) AS title,
			IF(attached.title != "", attached.title, node.title) AS label FROM {prefix}system_nodes node
			INNER JOIN {prefix}resources_files file USING(nid)
			INNER JOIN {prefix}resources_files_attached attached ON attached.fileid = file.nid
			WHERE attached.nodeid = ? AND attached.fileid = file.nid ORDER BY attached.weight', array
			(
				$ar->nid
			)
		)
		->fetchAll(PDO::FETCH_CLASS, 'resources_files_WdActiveRecord');

		// TODO-20100927: that's easy but not really good.

		$model = $core->models['resources.images'];

		foreach ($entries as &$entry)
		{
			if ($entry->constructor == 'resources.images')
			{
				$entry = $model[$entry->nid];
			}
		}

		return $entries;
	}
}