<?php

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
		return $this->model('system.nodes')->load($this->nodeid);
	}

	protected function __get_url()
	{
		return $this->node->url;
	}

	protected function __get_label()
	{
		$node = $this->node;

		return $node instanceof site_pages_WdActiveRecord ? $node->label : $node->title;
	}

	protected function __get_them_all($property)
	{
		return $this->node->$property;
	}
}