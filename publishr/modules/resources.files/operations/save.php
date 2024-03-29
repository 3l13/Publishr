<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class resources_files__save_WdOperation extends system_nodes__save_WdOperation
{
	/**
	 * @var WdUploaded|bool The optional file to save with the record.
	 */
	protected $file;

	/**
	 * @var array Accepted file types.
	 */
	protected $accept;

	/**
	 * Unset the `mime` and `size` properties because they cannot be modified by the user. If the
	 * `file` property is defined, which is the case when an asynchronous upload happend, it is
	 * copied to the `path` property.
	 *
	 * @see system_nodes__save_WdOperation::__get_properties()
	 */
	protected function __get_properties()
	{
		$properties = parent::__get_properties();

		unset($properties[File::MIME]);
		unset($properties[File::SIZE]);

		#
		# TODO-20100624: Using the 'file' property might be the way to go
		#

		if (isset($properties['file']))
		{
			$properties[File::PATH] = $properties['file'];
		}

		#
		# File:PATH is set to true when the file is not mandatory and there is no uploaded file in
		# order fot the form still validates, in which case the property must be unset, otherwise
		# the boolean is used a the new path.
		#

		if (isset($properties[File::PATH]) && $properties[File::PATH] === true)
		{
			unset($properties[File::PATH]);
		}

		return $properties;
	}

	public function __invoke()
	{
		$this->module->clean_repository();

		return parent::__invoke();
	}

	/**
	 * If PATH is not defined, we check for a file upload, which is required if the operation key
	 * is empty. If a file upload is found, the WdUploaded object is set as the operation `file`
	 * property, and the PATH parameter of the operation is set to the file location.
	 *
	 * Note that if the upload is not required - because the operation key is defined for updating
	 * an entry - the PATH parameter of the operation is set to TRUE to avoid error reporting from
	 * the form validation.
	 *
	 * TODO: maybe this is not ideal, since the file upload should be made optionnal when the form
	 * is generated to edit existing entries.
	 *
	 * @see WdOperation::control()
	 */
	protected function control(array $controls)
	{
		global $core;

		$this->file = null;
		$params = &$this->params;

		if (empty($params[File::PATH]))
		{
			$required = empty($this->key);
			$file = new WdUploaded(File::PATH, $this->accept, $required);

			$this->file = $file;

			if ($file->location)
			{
				$path = $core->config['repository.temp'] . '/' . basename($file->location) . $file->extension;
				$file->move($_SERVER['DOCUMENT_ROOT'] . $path, true);

				$params[File::PATH] = $path;

				if (empty($params[File::TITLE]))
				{
					$params[File::TITLE] = $file->name;
				}
			}
			else if (!$required)
			{
				$params[File::PATH] = true;
			}
		}

		return parent::control($controls);
	}

	/**
	 * The method validates unless there was an error during the file upload.
	 *
	 * @see save_WdOperation::validate()
	 */
	protected function validate()
	{
		$file = $this->file;

		if ($file && $file->er)
		{
			$this->form->log(File::PATH, 'Unable to upload file %file: :message.', array('%file' => $file->name, ':message' => $file->er_message));

			return false;
		}

		return parent::validate();
	}

	/**
	 * Trigger a 'resources.files.path.change' event when the path of the updated record is
	 * modified.
	 *
	 * @see system_nodes__save_WdOperation::process()
	 */
	protected function process()
	{
		$record = $this->record;
		$oldpath = $record ? $record->path : null;

		$rc = parent::process();

		if ($oldpath)
		{
			$newpath = $this->module->model->select('path')->find_by_nid($rc['key'])->rc;

			if ($oldpath != $newpath)
			{
				$event = WdEvent::fire
				(
					'resources.files.path.change', array
					(
						'path' => array
						(
							$oldpath,
							$newpath
						),

						'entry' => $record, // FIXME: record is null if new. attention to cache for existing records !!
						'module' => $this
					)
				);
			}
		}

		return $rc;
	}
}