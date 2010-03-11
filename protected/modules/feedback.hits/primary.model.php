<?php

class feedback_hits_WdModel extends WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		$properties += array
		(
			'last' => date('Y-m-d H:i:s')
		);

		return parent::save($properties, $key, $options);
	}
}