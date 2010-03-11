<?php

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
		# we update the "width" and "height" properties if the file is changed
		#

		$update = array();

		if ($uploaded || isset($properties[Video::PATH]))
		{
			if (!$key)
			{
				$key = $rc;
			}

			$path = $this->parent->select
			(
				Video::PATH, 'WHERE {primary} = ?', array
				(
					$key
				)
			)
			->fetchAndClose(PDO::FETCH_COLUMN);

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

		/*
		#
		# update poster
		#

		if (isset($properties[Video::POSTER]))
		{
			#
			# Only the files located in the repository temporary folder can be saved. We need to
			# check if the file is actually in the repository temporary folder.
			#

			$root = $_SERVER['DOCUMENT_ROOT'];
			$file = basename($properties[Video::POSTER]);
			$source = WdCore::getConfig('repository.temp') . '/' . $file;

			//wd_log("checking upload: $path");

			if (is_file($root . $source))
			{
				$path = $this->select(Video::PATH, 'WHERE {primary} = ?', array($rc))->fetchColumnAndClose();
				$path_parts = pathinfo($path);
				$destination = $path_parts['dirname'] . '/' . $path_parts['filename'] . '.jpeg';

				rename($root . $source, $root . $destination);

				$update = array
				(
					Video::POSTER => $destination
				);
			}
		}
		*/

		if ($update)
		{
			$this->update($update, $key);
		}

		return $rc;
	}

	/*
	public function delete($id)
	{
		$poster = $this->select(Video::POSTER, 'WHERE {primary} = ?', array($id))->fetchColumnAndClose();

		$rc = parent::delete($id);

		if ($rc && $poster)
		{
			$root = $_SERVER['DOCUMENT_ROOT'];

			if (is_file($root . $poster))
			{
				unlink($root . $poster);
			}
		}

		return $rc;
	}
	*/
}