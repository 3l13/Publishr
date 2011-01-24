<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_videos_WdModel extends resources_files_WdModel
{
	static protected $accept = array
	(
		'video/x-flv'
	);

	public function save(array $properties, $key=null, array $options=array())
	{
		$options += array
		(
			self::ACCEPT => self::$accept,
			self::UPLOADED => &$uploaded
		);

		$rc = parent::save($properties, $key, $options);

		if (!$rc)
		{
			return $rc;
		}

		#
		# we update the "width" and "height" properties if the file has changed
		#

		$update = array();

		if ($uploaded || isset($properties[Video::PATH]))
		{
			if (!$key)
			{
				$key = $rc;
			}

			$path = $this->parent->select('path')->find_by_nid($key)->rc;

			if ($path)
			{
				$flv = new Flvinfo();

				$info = $flv->getInfo($_SERVER['DOCUMENT_ROOT'] . $path);

				$w = 0;
				$h = 0;
				$duration = 0;

				if ($info && $info->hasVideo)
				{
					$w = $info->video->width;
					$h = $info->video->height;
					$duration = $info->duration;
				}

				$update = array
				(
					Video::WIDTH => $w,
					Video::HEIGHT => $h,
					Video::DURATION => $duration
				);
			}
		}

		if ($update)
		{
			$this->update($update, $key);
		}

		return $rc;
	}
}