<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

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