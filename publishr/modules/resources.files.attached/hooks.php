<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_files_attached_WdHooks
{
	static public function get_attached_files(system_nodes_WdActiveRecord $ar)
	{
		global $core;

		$entries = $core->db->query
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

	static public function event_alter_block_edit(WdEvent $event)
	{
		global $core;

		$target = $event->target;

		if ($target instanceof resources_files_WdModule)
		{
			return;
		}

		$scope = $core->registry['resources_files_attached.scope'];

		if (!$scope)
		{
			return;
		}

		$scope = explode(',', $scope);

		if (!in_array($target->flat_id, $scope))
		{
			return;
		}

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'attached_files' => array
					(
						'title' => '.attached_files',
						'class' => 'form-section flat'
					)
				),

				WdElement::T_CHILDREN => array
				(
					new WdAttachedFilesElement
					(
						array
						(
							WdElement::T_GROUP => 'attached_files',

							WdAttachedFilesElement::T_NODEID => $event->key,
							WdAttachedFilesElement::T_HARD_BOND => true
						)
					)
				)
			)
		);
	}
}