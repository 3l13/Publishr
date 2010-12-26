<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class organize_lists_WdActiveRecord extends system_nodes_WdActiveRecord implements Iterator
{
	public $scope;
	public $description;

	protected function __get_nodes()
	{
		global $core;

		$nodes = $core->models['organize.lists/nodes']
		->select('lnode.*, node.constructor')
		->joins('INNER JOIN {prefix}system_nodes node ON lnode.nodeid = node.nid')
		->where('listid = ? AND node.is_online = 1 AND lnode.nodeid = node.nid', $this->nid)
		->order('weight')
		->all(PDO::FETCH_CLASS, 'organize_lists_nodes_WdActiveRecord');

		$ids_by_constructor = array();
		$nodes_by_id = array();

		foreach ($nodes as $node)
		{
			$nid = $node->nodeid;

			$nodes_by_id[$nid] = $node;
			$ids_by_constructor[$node->constructor][] = $nid;
		}

		foreach ($ids_by_constructor as $constructor => $keys)
		{
			$model = $core->models[$constructor];

			$constructor_nodes = $model->find($keys);

			foreach ($constructor_nodes as $node)
			{
				$nid = $node->nid;

				if (!$node->is_online)
				{
					unset($nodes_by_id[$nid]);

					continue;
				}

				$nodes_by_id[$nid]->node = $node;
			}
		}

		return $nodes;
	}

	/*
	 * iterator
	 */

	private $position = 0;

    function rewind()
    {
    	$this->position = 0;
    }

    function current()
    {
    	return $this->nodes[$this->position];
    }

    function key()
    {
    	return $this->position;
    }

    function next()
    {
    	++$this->position;
    }

    function valid()
    {
    	return isset($this->nodes[$this->position]);
    }
}