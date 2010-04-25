<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_forms_WdModel extends system_nodes_WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		if (isset($properties[Form::CONFIG]))
		{
			$properties[Form::SERIALIZED_CONFIG] = serialize($properties[Form::CONFIG]);
		}

		return parent::save($properties, $key, $options);
	}
}
