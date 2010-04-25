<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_WdModel extends system_nodes_WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		if ($key && isset($properties[Page::PARENTID]) && $key == $properties[Page::PARENTID])
		{
			throw new WdException('A page connot be its own parent');
		}

		return parent::save($properties, $key, $options);
	}

	public function loadAll($completion=null, array $args=array(), array $options=array())
	{
		return $this->select
		(
			array
			(
				'node.*',
				'page.*',
				'hasChild' => '(SELECT 1 FROM {prefix}site_pages WHERE parentid = page.nid LIMIT 1)'
			),

			$completion, $args, $options + $this->loadall_options
		);
	}
	
	/**
	 * Load the nested nodes of a tree.
	 * 
	 * Because the children
	 * 
	 * @param unknown_type $parentid
	 * @param unknown_type $max_depth
	 */

	public function loadAllNested($parentid=null, $max_depth=false)
	{
		$ids = $this->select
		(
			array('nid', 'parentid'), 'ORDER BY weight, created'
		)
		->fetchAll(PDO::FETCH_OBJ);

		$tree = self::nestNodes($ids, $by_id);
		
		self::setNodesDepth($tree, $max_depth);

		if ($parentid)
		{
			if (empty($by_id[$parentid]))
			{
				return;
			}

			$tree = array($by_id[$parentid]);
		}

		$nodes = self::levelNodesById($tree);
		
		$ids = array();
		$ordered = array();
		
		foreach ($nodes as $nid => $node)
		{
			$ordered[$nid] = $entry = $this->retrieve($nid);
			
			if ($entry)
			{
				continue;
			}
			
			$ids[] = $nid;
		}
		
//		echo t('ordered: \1', array($ordered));

		if ($ids)
		{
			$entries = $this->loadAll('WHERE nid IN(' . implode(',', $ids) . ')')->fetchAll();
			
			foreach ($entries as $entry)
			{
				$nid = $entry->nid;
				
				$this->store($nid, $entry); // TODO: move this in the loadAll() method someday
				
				$ordered[$nid] = $entry;
			}
		}
		
//		echo t('ordered final: \1', array($ordered));

		// FIXME: est-ce qu'il n'y a pas un chance de se retrouver avec des enfants en double ?
		
		return self::nestNodes($ordered);
	}
	
	/**
	 * Nest an array of nodes, using their `parentid` property.
	 * 
	 * Children are stored in the `children` property of their parents.
	 * 
	 * Parent is stored in the `parent` property of its children.
	 * 
	 * @param array $entries The array of nodes.
	 * @param array $parents The array of nodes, where the key is the entry's `nid`.
	 */

	static public function nestNodes($entries, &$entries_by_ids=null)
	{
		#
		# In order to easily access entries, they are store by their Id in an array.
		#

		$entries_by_ids = array();

		foreach ($entries as $entry)
		{
			$entry->children = array();

			$entries_by_ids[$entry->nid] = $entry;
		}

		#
		#
		#

		$tree = array();

		foreach ($entries_by_ids as $entry)
		{
			if (!$entry->parentid || empty($entries_by_ids[$entry->parentid]))
			{
				$tree[] = $entry;

				continue;
			}

			$entry->parent = $entries_by_ids[$entry->parentid];
			$entry->parent->children[] = $entry;
		}

		return $tree;
	}
	
	/**
	 * Walk the nodes and sets their depth level.
	 * 
	 * @param $nodes The nodes to walk through.
	 * @param $max_depth The maximum depth level of the nodes. Nodes beyond the max_depth are removed.
	 * Default to false (no maximum depth level).
	 * @param $depth The depth level to start from. Default to 0.
	 */
	
	static public function setNodesDepth($nodes, $max_depth=false, $depth=0)
	{
		foreach ($nodes as $node)
		{
			$node->depth = $depth;
			
			if ($node->children)
			{
				if ($max_depth !== false && $max_depth == $depth)
				{
					#
					# The `children` property is unset rather then emptied, making the loading
					# of children possible by accessing the `children` property.
					# 
					
					unset($node->children);
				}
				else
				{
					self::setNodesDepth($node->children, $max_depth, $depth + 1);
				}
			}
		}
	}
	
	/**
	 * Creates an array from all the nested nodes, where keys are node's Id.
	 * 
	 * @param $nodes
	 */
	
	static public function levelNodesById($nodes)
	{
		$by_id = array();
		
		foreach ($nodes as $node)
		{
			$by_id[$node->nid] = $node;

			if ($node->children)
			{
				$by_id += self::levelNodesById($node->children);
			}
		}
		
		return $by_id;
	}
}