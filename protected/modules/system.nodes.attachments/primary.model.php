<?php

class system_nodes_attachments_WdModel extends WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		$properties['is_mandatory'] = !empty($properties['is_mandatory']);

		return parent::save($properties, $key, $options);
	}
}