<?php

class feedback_forms_WdModel extends system_nodes_WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		if (isset($properties['config']))
		{
			$properties['serializedconfig'] = serialize($properties['config']);
		}

		return parent::save($properties, $key, $options);
	}
}
