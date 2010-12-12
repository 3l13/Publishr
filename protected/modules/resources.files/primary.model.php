<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_files_WdModel extends system_nodes_WdModel
{
	const ACCEPT = '#files-accept';
	const UPLOADED = '#files-uploaded';

	// TODO-20091224: Move the file handling to the `operation_save` callback.

	public function save(array $values, $key=null, array $options=array())
	{
		if (defined('WDPUBLISHER_CONVERTING'))
		{
			return parent::save($values, $key, $options);
		}

		#
		# because the newly uploaded file might not overrite the previous file if there extensions
		# don't match, we use the $delete variable to delete the previous file. the variable
		# is defined after an upload.
		#

		$delete = null;

		#
		# $previous_title is used to check if the file has to been renamed.
		# It is set to the last value of the entry, or NULL if we are creating a
		# new one.
		#
		# If nedded, the file is renamed after the entry has been saved.
		#

		$title = null;

		$previous_title = null;
		$previous_path = null;

		if (isset($values[File::TITLE]))
		{
			$title = $values[File::TITLE];
		}

		#
		# If we are modifying an entry, we load its previous values to check for updates related
		# to the title.
		#

		if ($key)
		{
			#
			# load previous entry to check for changes
			#

			$previous = $this->select('title, path, mime')->where('{primary} = ?', $key)->one;

			#
			# extract previous to obtain previous_title, previous_path and previous_mime
			#

			extract($previous, EXTR_PREFIX_ALL, 'previous');

			$values[File::MIME] = $previous_mime;
		}

		if (!empty($values[File::PATH]))
		{
			#
			# Only the files located in the repository temporary folder can be saved. We need to
			# check if the file is actually in the repository temporary folder. The file is
			# required for new entries, so if the file is not defined here, the save process will
			# fail.
			#

			$root = $_SERVER['DOCUMENT_ROOT'];
			$file = basename($values[File::PATH]);
			$path = WdCore::$config['repository.temp'] . '/' . $file;

			//wd_log("checking upload: $path");

			if (is_file($root . $path))
			{
				$mime = WdUploaded::getMIME($root . $path);
				$size = filesize($root . $path);

				//wd_log('found file: \3, mime: \1, size: \2', array($mime, $size, $path));

				$delete = $previous_path;

				$previous_path = $path;

				$values[File::MIME] = $mime;
				$values[File::SIZE] = $size;

				#
				# setting `previous_title` to null will force the update
				#

				$previous_title = null;

				#
				# setting the UPLOADED value in the options
				#

				if (array_key_exists(self::UPLOADED, $options))
				{
					$options[self::UPLOADED] = $file;
				}
			}
			else if (!$key)
			{
				wd_log_error('The file %file is not located in the repository temporary folder', array('%file' => $file));

				return false;
			}
		}

		#
		# we check file update through the PATH slot
		#

		$file = new WdUploaded(File::PATH, isset($options[self::ACCEPT]) ? $options[self::ACCEPT] : null);

//		wd_log('file: \1, files: \2', array($file, $_FILES));

		if ($file->er)
		{
			wd_log_error('Unable to upload file %file: :message.', array('%file' => $file->name, ':message' => $file->er_message));

			return false;
		}
		else if ($file->location)
		{
			#
			# A file has been uploaded, we move the file from the PHP temporary directory
			# to the temporary directory of our repository.
			#
			# The `delete` variable is set to the previous file path, so that the previous file is deleted
			# before we replace it with the new file.
			#
			# `previous_title` is set to `null` to force renaming, which will move the file
			# from the repository temporary directory to the final destination of the file.
			#

			$delete = $previous_path;

			$previous_title = null;
			$previous_path = WdCore::$config['repository.temp'] . '/' . date('YmdHis') . '-' . basename($file->location) . $file->extension;

			$file->move($_SERVER['DOCUMENT_ROOT'] . $previous_path);

			if (array_key_exists(self::UPLOADED, $options))
			{
				$options[self::UPLOADED] = $file;
			}

			$values[File::MIME] = $file->mime;
			$values[File::SIZE] = $file->size;
		}
		else
		{
			#
			# we need to delete our object, otherwise it will be used to move the file
			#

			$file = null;
		}

		#
		# before we continue, we have to check if we can actually move the file to the repository
		#

		$path = self::makePath($key, array('path' => $previous_path) + (array) $file + $values);

		//wd_log('path: \1, preivous: \2', array($path, $previous_path));

		//wd_log('file: \1, values: \6 path: \2 ?= \3, title: \4 ?= \5, umask: \6 ', array($file, $previous_path, $path, $previous_title, $title, $values, umask()));

		$root = $_SERVER['DOCUMENT_ROOT'];
		$parent = dirname($path);

		if (!is_dir($root . $parent))
		{
			mkdir($root . $parent, 0777, true);
		}

//		wd_log('path: \1', array($path));

		if (!is_writable($root . $parent))
		{
			wd_log_error('Unable to save file, The directory %directory is not writable', array('%directory' => $parent));

			return false;
		}











		$key = parent::save($values, $key, $options);

		if (!$key)
		{
			return $key;
		}

		#
		# change path according to node's title
		#

//		wd_log("path: $previous_path ?= $path, title: $previous_title ?= $title");

		if (($path != $previous_path) || (!$previous_title || ($previous_title != $title)))
		{
			$path = self::makePath($key, array('path' => $previous_path) + (array) $file + $values);

			//wd_log('previous_path: %previous_path, path: %path', array('%previous_path' => $previous_path, '%path' => $path));

			if ($delete && is_file($root . $delete))
			{
				unlink($root . $delete);
			}

			$destination = $root . $path;

			if ($file)
			{
				#
				# If the destination already exists, we need to delete the file before we
				# can replace it.
				#

				if (file_exists($destination))
				{
					unlink($destination);
				}

				$ok = $file->move($destination);
			}
			else
			{
				$ok = rename($root . $previous_path, $root . $path);
			}

			if ($ok)
			{
				$this->update
				(
					array
					(
						File::PATH => $path
					),

					$key
				);
			}
			else
			{
				wd_log_error('Unable to rename %previous to %path', array('%previous' => $previous_path, '%path' => $path));
			}
		}

		return $key;
	}

	public function delete($id)
	{
		$path = $this->select('path')->where('{primary} = ?', $id)->column;

		$rc = parent::delete($id);

		if ($rc && $path)
		{
			$root = $_SERVER['DOCUMENT_ROOT'];

			if (is_file($root . $path))
			{
				unlink($root . $path);
			}
		}

		return $rc;
	}

	static protected function makePath($key, array $values)
	{
		//wd_log('makePath with: \1', array($values));

		$rc = WdCore::$config['repository.files'];

		$mime = $values[File::MIME];

		$base = dirname($mime);

		if ($base == 'application')
		{
			$base = basename($mime);
		}

		if (!in_array($base, array('image', 'audio', 'pdf', 'zip')))
		{
			$base = 'bin';
		}

		$rc .= '/' . $base . '/' . ($key ? $key : 'temp') . '-' . wd_normalize($values[File::TITLE]);

		#
		# append extension
		#

		if (isset($values['extension']))
		{
			$extension = $values['extension'];
		}
		else
		{
			$previous_path = $values['path'];

			$pos = strrpos($previous_path, '.');

			$extension = $pos === false ? '.file' : substr($previous_path, $pos);
		}

		$rc .= $extension;

		//wd_log('path: \1', array($rc));

		return $rc;
	}
}
