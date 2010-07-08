<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class user_members_WdModel extends user_users_WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		$photo = null;
		$photo_path = null;

		if (isset($properties['photo']) && is_object($properties['photo']))
		{
			$photo = $properties['photo'];

//			wd_log('photo: \1', array($photo));

			$filename = wd_normalize($properties['username']) . $photo->extension;
			$photo_path = WdCore::getConfig('repository') . '/files/members/' . $filename;
			$properties['photo'] = $photo_path;
		}

		$rc = parent::save($properties, $key, $options);

//		wd_log('photo: \1, properties: \2', array($photo, $properties));

		if ($rc && $photo)
		{
			$photo->move($_SERVER['DOCUMENT_ROOT'] . $photo_path, true);
		}

		return $rc;
	}
}