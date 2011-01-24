<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_files_WdModule extends system_nodes_WdModule
{
	const OPERATION_UPLOAD = 'upload';
	const OPERATION_UPLOAD_RESPONSE = 'uploadResponse';

	const ACCEPT = '#files-accept';
	const UPLOADED = '#files-uploaded';
	const UPLOADER_CLASS = 'uploader class';

	const SESSION_UPLOAD_RESPONSE = 'resources.files.upload.responses';

	static protected $repository = array();

	static protected function repository($name)
	{
		if (empty(self::$repository[$name]))
		{
			self::$repository[$name] = WdCore::$config['repository'] . '/' . $name . '/';
		}

		return self::$repository[$name];
	}

	protected $accept = null;
	protected $uploader_class = 'WdFileUploadElement';

	public function install()
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

		#
		#
		#

		$repositories = array(self::repository('temp'), self::repository('files'));

		foreach ($repositories as $repository)
		{
			if (is_dir($root . $repository))
			{
				continue;
			}

			#
			# is parent writable ?
			#

			$parent = dirname($repository);

			if (!is_writable($root . $parent))
			{
				wd_log_error
				(
					'Unable to create %directory directory, parent %parent is not writtable', array
					(
						'%directory' => $repository,
						'%parent' => $parent
					)
				);

				return false;
			}

			if (!mkdir($root . $repository))
			{
				wd_log_error('Unable to create %directory directory', array('%directory' => $repository));

				return false;
			}
		}

		return parent::install();
	}

	public function isInstalled()
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

		if (!is_dir($root . WdCore::$config['repository.temp']))
		{
			return false;
		}

		if (!is_dir($root . WdCore::$config['repository.files']))
		{
			return false;
		}

		return parent::isInstalled();
	}

	protected function controls_for_operation_upload(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_CREATE
		);
	}

	/**
	 * If PATH is not defined, we check for a file upload, which is required if the operation key
	 * is not provided. If an upload is found, the WdUploaded object is set as the operation 'file'
	 * property, and the PATH parameter of the operation is set to the file location.
	 *
	 * Note that if the upload is not required - because the operation key is defined for updating
	 * an entry - the PATH parameter of the operation is set to TRUE to avoid error reporting from
	 * the form validation.
	 *
	 * TODO: maybe this is not ideal, since the file upload should be made optionnal when the form
	 * is generated to edit existing entries.
	 *
	 * @param WdOperation $operation
	 * @param array $controls
	 * @return boolean Control success.
	 */

	protected function control_operation_save(WdOperation $operation, array $controls)
	{
		self::clean_repository();

		$operation->file = null;

		if (empty($operation->params[File::PATH]))
		{
			$required = empty($operation->key);

			$file = new WdUploaded(File::PATH, $this->accept, $required);

			$operation->file = $file;
			$operation->params[File::PATH] = $required ? $file->location : true;
		}

		return parent::control_operation($operation, $controls);
	}

	/**
	 *
	 * @see $wd/wdpublisher/includes/WdPModule#validate_operation_save($operation)
	 */

	protected function validate_operation_save(WdOperation $operation)
	{
		$file = $operation->file;

		if ($file)
		{
			if ($file->er)
			{
				$operation->form->log(File::PATH, 'Unable to upload file %file: :message.', array('%file' => $file->name, ':message' => $file->er_message));

				return false;
			}

			if ($file->location || $operation->params[File::PATH] === true)
			{
				unset($operation->params[File::PATH]);
			}
		}

		return parent::validate_operation_save($operation);
	}

	protected function control_properties_for_operation_save(WdOperation $operation)
	{
		$properties = parent::control_properties_for_operation_save($operation);

		unset($properties[File::MIME]);
		unset($properties[File::SIZE]);

		#
		# TODO-20100624: Using the 'file' property might be the way to go
		#

		if (isset($properties['file']))
		{
			$properties[File::PATH] = $properties['file'];
		}

		return $properties;
	}

	protected function operation_save(WdOperation $operation)
	{
		$record = null;
		$oldpath = null;

		if ($operation->record)
		{
			$record = $operation->record;
			$oldpath = $record->path;
		}

		$rc = parent::operation_save($operation);

		if ($record && $oldpath)
		{
			$newpath = $this->model->select('path')->find_by_nid($record->nid)->rc;

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

						'entry' => $record,
						'module' => $this
					)
				);
			}
		}

		return $rc;
	}

	protected function validate_operation_upload(WdOperation $operation)
	{
		self::clean_repository();

		#
		# we set the HTTP_ACCEPT ourselves to force JSON output
		#

		// TODO-20110106: is this still needed ?

		$_SERVER['HTTP_ACCEPT'] = 'application/json';

		#
		# TODO-20100624: we use 'Filedata' because it's used by Swiff.Uploader, we have to change
		# that as soon as possible.
		#

		$file = new WdUploaded('Filedata', $this->accept, true);

		$operation->response->file = $file;

		if ($file->er)
		{
			wd_log_error($file->er_message);

			$operation->response->file = $file;

			return false;
		}

		$operation->file = $file;

		return true;
	}

	protected function operation_upload(WdOperation $operation, array $options=array())
	{
		#
		# the `file` property is set by the validator
		#

		$file = $operation->file;
		$path = null;

		if ($file->location)
		{
			$path = WdCore::$config['repository.temp'] . '/' . basename($file->location) . $file->extension;

			$destination = $_SERVER['DOCUMENT_ROOT'] . $path;

			$file->move($destination, true);
		}

		$file->location = wd_strip_root($file->location);

		#
		#
		#

		global $core;

		$core->session;

		$id = uniqid();

		$_SESSION[self::SESSION_UPLOAD_RESPONSE][$id] = array
		(
			'name' => $file->name,
			'path' => $path,
			'fields' => array
			(
				'title' => $file->name
			)
		);

		$operation->terminus = true;

		return $id;
	}

	protected function validate_operation_uploadResponse(WdOperation $operation)
	{
		global $core;

		$core->session;

		$id = $operation->params['uploadId'];
		$key = self::SESSION_UPLOAD_RESPONSE;

		if (empty($_SESSION[$key][$id]))
		{
			return false;
		}

		$operation->upload = $_SESSION[$key][$id];
		$count = count($_SESSION[$key]);

		if ($count > 10)
		{
			$_SESSION[$key] = array_splice($_SESSION[$key], $count - 10);
		}

		return true;
	}

	protected function operation_uploadResponse(WdOperation $operation, array $options=array())
	{
		$operation->terminus = true;

		#
		# We need to create a document since the uploader element might use it to add CSS or JS
		# resources.
		#

		global $document;

		$document = new WdDocument();

		$options += array
		(
			self::UPLOADER_CLASS => $this->uploader_class
		);

		$class = $options[self::UPLOADER_CLASS];
		$upload = $operation->upload;

		return array
		(
			'element' => (string) new $class
			(
				array
				(
					WdElement::T_FILE_WITH_LIMIT => true,

					'name' => isset($_GET['name']) ? $_GET['name'] : File::PATH,
					'value' => $upload['path']
				)
			),

			'title' => $upload['name'],
			'fields' => $upload['fields']
		);
	}

	protected function controls_for_operation_download(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_RECORD => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	/**
	 * Extends the control_record_for_operation() method to check the availability of the record
	 * to the requesting user.
	 *
	 * @param WdOperation $operation An operation object.
	 * @return WdActiveRecord $record The target record for the operation.
	 * @throws WdHTTPException with HTTP code 401, if the user is a guest and the record is
	 * offline.
	 */

	protected function control_record_for_operation_download(WdOperation $operation)
	{
		global $core;

		$record = parent::control_record_for_operation($operation);

		if ($core->user->is_guest() && !$record->is_online)
		{
			throw new WdHTTPException
			(
				'The requested resource requires authentication: %resource', array
				(
					'%resource' => $this->id . '/' . $operation->key
				),

				401
			);
		}

		return $record;
	}

	protected function operation_download(WdOperation $operation)
	{
		$record = $operation->record;

		// TODO-20090512: Implement Accept-Range

		$filename = $record->title . $record->extension;
		$filename = strtr($filename, '"', '');

		#
		# http://tools.ietf.org/html/rfc2183 /
		#

		if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)
		{
			$filename = wd_remove_accents($filename);
		}

		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: ' . $record->mime);
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '. $record->size);
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: public');

		$fh = fopen($_SERVER['DOCUMENT_ROOT'] . $record->path, 'rb');

		if ($fh)
	    {
			#
			# Reset time limit for big files
			#

	    	if (!ini_get('safe_mode'))
	    	{
				set_time_limit(0);
	    	}

			while (!feof($fh) && !connection_status())
			{
				echo fread($fh, 1024 * 8);

				#
				# flushing frees memory used by the PHP buffer
				#

				flush();
			}

			fclose($fh);
		}

		exit;
	}

	static protected function clean_repository($repository=':repository.temp', $lifetime=3600)
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

		if ($repository{0} == ':')
		{
			$repository = WdCore::$config[substr($repository, 1)];
		}

		if (!is_dir($root . $repository))
		{
			wd_log_error('The directory %directory does not exists', array('%directory' => $repository));

			return;
		}

		if (!is_writable($root . $repository))
		{
			wd_log_error('The directory %directory is not writtable', array('%directory' => $repository));

			return;
		}

		$dh = opendir($root . $repository);

		if (!$dh)
		{
			return;
		}

		$now = time();
		$location = getcwd();

		chdir($root . $repository);

		while ($file = readdir($dh))
		{
			if ($file{0} == '.')
			{
				continue;
			}

			$stat = stat($file);

			if ($now - $stat['ctime'] > $lifetime)
			{
				unlink($file);

				wd_log
				(
					'The temporary file %file has been deleted form the repository %directory', array
					(
						'%file' => $file,
						'%directory' => $repository
					)
				);
			}
		}

		chdir($location);

		closedir($dh);
	}

	/*
	**

	BLOCKS

	**
	*/

	protected function block_config()
	{
		return array
		(
			WdElement::T_CHILDREN => array
			(
				"local[$this->flat_id.max_file_size]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Taille maximale des fichiers déposés',
						WdElement::T_LABEL => 'Ko',
						WdElement::T_GROUP => 'primary',
						WdElement::T_DEFAULT => 16000,

						'size' => 6,
						'style' => 'text-align: right'
					)
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission, array $options=array())
	{
		global $core, $document;

		$folder = WdCore::$config['repository.temp'];

		if (!is_writable($_SERVER['DOCUMENT_ROOT'] . $folder))
		{
			return array
			(
				WdElement::T_CHILDREN => array
				(
					t('The folder %folder is not writable !', array('%folder' => $folder))
				)
			);
		}

		$document->css->add('public/edit.css');
		$document->js->add('public/edit.js');

		#
		# options
		#

		$options += array
		(
			self::ACCEPT => $this->accept,
			self::UPLOADER_CLASS => $this->uploader_class
		);

		$accept = $options[self::ACCEPT];
		$uploader_class = $options[self::UPLOADER_CLASS];

		#
		# UPLOADED is set when the file has already been updated
		# and is available on our host
		#

		$values = array();
		$properties += array
		(
			File::NID => null,
			File::PATH => null,
			self::UPLOADED => null
		);

		$entry_nid = $properties[File::NID];
		$entry_path = $properties[File::PATH];

		$uploaded_path = $properties[self::UPLOADED];
		$uploaded_mime = null;

		#
		# check uploaded file
		#

		$file = new WdUploaded(File::PATH, $accept);

		if ($file->location)
		{
			$values[File::TITLE] = $file->name;

			$uploaded_mime = $file->mime;
			$uploaded_path = WdCore::$config['repository.temp'] . '/' . basename($file->location) . $file->extension;

			$file->move($_SERVER['DOCUMENT_ROOT'] . $uploaded_path);

			if (array_key_exists(self::UPLOADED, $options))
			{
				$options[self::UPLOADED] = $file;
			}
		}

		// FIXME: now that we use a flash uploader, will the PATH defined in HIDDENS be a problem ?

		$values[File::PATH] = $uploaded_path ? $uploaded_path : $entry_path;

		#
		# elements
		#

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdForm::T_HIDDENS => array
				(
					File::PATH => $uploaded_path,
					File::MIME => $uploaded_mime,

					self::UPLOADED => $uploaded_path
				),

				WdForm::T_VALUES => $values,

				/*
				WdElement::T_GROUPS => array
				(
					'file' => array
					(
						'title' => 'Fichier'
					)
				),
				*/

				WdElement::T_CHILDREN => array
				(
					File::PATH => new $uploader_class
					(
						array
						(
							WdForm::T_LABEL => 'Fichier',
							WdElement::T_REQUIRED => empty($entry_nid),
							WdElement::T_FILE_WITH_LIMIT => $core->working_site->metas[$this->flat_id . '.max_file_size'],
							WdElement::T_WEIGHT => -100
						)
					),

					/*
					File::PATH => new WdElement
					(
						WdElement::E_FILE, array
						(
							WdForm::T_LABEL => 'Fichier',
							WdElement::T_REQUIRED => empty($entry_nid),
							WdElement::T_FILE_WITH_REMINDER => true,
							WdElement::T_FILE_WITH_LIMIT => $core->working_site->metas[$this->flat_id . '.max_file_size'],
							WdElement::T_WEIGHT => -100
						)
					),
					*/

					File::DESCRIPTION => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Description',
							WdElement::T_WEIGHT => 50,

							'rows' => 5
						)
					)
				)
			)
		);
	}

	protected function block_manage()
	{
		return new resources_files_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array('title', 'mime', 'size', 'uid', 'is_online', 'modified')
			)
		);
	}
}