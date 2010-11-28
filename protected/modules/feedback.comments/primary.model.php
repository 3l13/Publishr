<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdModel extends WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		$properties += array
		(
			Comment::STATUS => 'pending',
			Comment::NOTIFY => 'no'
		);

		if (!in_array($properties[Comment::NOTIFY], array('no', 'yes', 'author', 'done')))
		{
			throw new WdException
			(
				'Invalid value for %property property (%value)', array
				(
					'%property' => Comment::NOTIFY,
					'%value' => $properties[Comment::NOTIFY]
				)
			);
		}

		return parent::save($properties, $key, $options);
	}
}