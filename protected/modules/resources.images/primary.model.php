<?php

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

			$path = $this->parent->select(File::PATH, 'WHERE {primary} = ?', array($key))->fetchColumnAndClose();

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