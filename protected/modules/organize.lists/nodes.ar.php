<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class organize_lists_nodes_WdActiveRecord extends WdActiveRecord
{
	public function __construct()
	{
		if (empty($this->label))
		{
			unset($this->label);
		}
	}

	public function has_property($property)
	{
		if (parent::has_property($property))
		{
			return true;
		}

		return $this->node->has_property($property);
	}

	public function __call($name, $args)
	{
		$node = $this->node;

		if (!$node)
		{
			throw new WdException('Unable to load node %node', array($this->nodeid));
		}

		return call_user_func_array(array($node, $name), $args);
	}

	protected function __get_node()
	{
		global $core;

		return $core->models[isset($this->constructor) ? $this->constructor : 'system.nodes'][$this->nodeid];
	}

	protected function __get_label()
	{
		$node = $this->node;

		return $node instanceof site_pages_WdActiveRecord ? $node->label : $node->title;
	}

	protected function __defer_get($property, &$success)
	{
		$success = true;

		return $this->node->$property;
	}
}