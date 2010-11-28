<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_images_WdModel extends resources_files_WdModel
{
	protected static $accept = array
	(
		'image/gif', 'image/png', 'image/jpeg'
	);

	public function save(array $properties, $key=null, array $options=array())
	{
		$options += array
		(
			self::ACCEPT => self::$accept,
			self::UPLOADED => &$uploaded
		);

		$rc = parent::save($properties, $key, $options);

		#
		# we update the "width" and "height" properties if the file is changed
		#

		if ($rc && ($uploaded || isset($properties[File::PATH])))
		{
			if (!$key)
			{
				$key = $rc;
			}

			$path = $this->parent->_select(File::PATH)->where(array('{primary}' => $key))->column();

			if ($path)
			{
				list($w, $h) = getimagesize($_SERVER['DOCUMENT_ROOT'] . $path);

				$this->update
				(
					array
					(
						resources_images_WdActiveRecord::WIDTH => $w,
						resources_images_WdActiveRecord::HEIGHT => $h
					),

					$key
				);
			}
		}

		return $rc;
	}
}