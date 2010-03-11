<?php

class feedback_comments_WdModel extends WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		$properties += array
		(
			Comment::STATUS => 'pending',
			Comment::NOTIFY => 'no'
		);

		return parent::save($properties, $key, $options);
	}
}