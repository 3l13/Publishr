<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_images_WdActiveRecord extends resources_files_WdActiveRecord
{
	const WIDTH = 'width';
	const HEIGHT = 'height';

	protected function __get_thumbnail()
	{
		return $this->thumbnail('primary');
	}

	public function thumbnail($version)
	{
		global $registry;

		$path = $registry['thumbnailer.versions.' . $version . '.path'];
		$src = $this->path;

		if ($path && strpos($src, $path) === 0)
		{
			$src = substr($src, strlen($path));
		}

		return WdOperation::encode
		(
			'thumbnailer', 'get', array
			(
				'src' => $src,
				'version' => $version
			),

			'r'
		);
	}
}