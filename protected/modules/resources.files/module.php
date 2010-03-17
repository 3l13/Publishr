<?php

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
			self::$repository[$name] = WdCore::getConfig('repository') . '/$' . $name . '/';
		}

		return self::$repository[$name];
	}

	protected $accept;
	protected $uploader_class = 'WdFileUploadElement';

	public function run()
	{
		self::cleanRepository(self::repository('temp'), 3600);

		return parent::run();
	}

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
		if (!is_dir($_SERVER['DOCUMENT_ROOT'] . self::repository('temp')))
		{
			return false;
		}

		if (!is_dir($_SERVER['DOCUMENT_ROOT'] . self::repository('files')))
		{
			return false;
		}

		return parent::isInstalled();
	}

	public function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_UPLOAD => array
			(
				self::CONTROL_PERMISSION => PERMISSION_CREATE
			)
		)

		+ parent::getOperationsAccessControls();
	}

	/**
	 * If PATH is not defined, we check for a file upload, which is mandatory if the operation key
	 * is not provided. If an upload is found, the WdUploaded object is set as the operation 'file'
	 * property, and the PATH parameter of the operation is set to the file location.
	 *
	 * Note that if the upload is not mandatory - because the operation key is defined for updating
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
		$operation->file = null;

		if (empty($operation->params[File::PATH]))
		{
			$mandatory = empty($operation->key);

			$file = new WdUploaded(File::PATH, $this->accept, $mandatory);

			$operation->file = $file;
			$operation->params[File::PATH] = $mandatory ? $file->location : true;
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

	protected function operation_save(WdOperation $operation)
	{
		unset($operation->params[File::MIME]);
		unset($operation->params[File::SIZE]);

		#
		#
		#

		$entry = null;
		$oldpath = null;

		if ($operation->entry)
		{
			$entry = $operation->entry;
			$oldpath = $entry->path;
		}

		$rc = parent::operation_save($operation);

		if ($rc && $entry && $oldpath)
		{
			$newpath = $this->model()->select('path', 'WHERE {primary} = ?', array($entry->nid))->fetchColumnAndClose();

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

						'entry' => $entry,
						'module' => $this
					)
				);
			}
		}

		return $rc;
	}


















	protected function validate_operation_upload(WdOperation $operation)
	{
		#
		# we set the HTTP_ACCEPT ourselves to force JSON output
		#

		$_SERVER['HTTP_ACCEPT'] = 'application/json';

		$file = new WdUploaded('Filedata', null, true);

		if ($file->er)
		{
			wd_log_error($file->er);

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
			$path = WdCore::getConfig('repository.temp') . '/' . basename($file->location) . $file->extension;

			$destination = $_SERVER['DOCUMENT_ROOT'] . $path;

			$file->move($destination, true);
		}

		#
		#
		#

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

					'name' => File::PATH,
					'value' => $upload['path']
				)
			),

			'title' => $upload['name'],
			'fields' => $upload['fields']
		);
	}

	protected function validate_operation_download(WdOperation $operation)
	{
		if (empty($operation->params[File::NID]))
		{
			return false;
		}

		$nid = (int) $operation->params[File::NID];

		$entry = $this->model()->load($nid);

		if (!$entry)
		{
			header('HTTP/1.1 404 Not Found');

			die('Unknown resource');
		}

		global $user;

		if ($user->isGuest() && !$entry->is_online)
		{
			header('HTTP/1.1 403 Forbidden');

			die('Resource is offline');
		}

		$operation->entry = $entry;

		return true;
	}

	protected function operation_download(WdOperation $operation)
	{
		$entry = $operation->entry;

		$path = $entry->path;
		$extension = substr($path, strrpos($path, '.') + 1);
		$filename = $entry->title . '.' . $extension;

		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: ' . $entry->mime);
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '. $entry->size);
		header('Cache-Control: no-cache, must-revalidate');
		//header('Accept-Ranges: bytes');
		header('Pragma: public');

		$fh = fopen($_SERVER['DOCUMENT_ROOT'] . $path, 'rb');

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



	static protected function cleanRepository($repository, $lifetime=3600)
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

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

	protected function block_edit(array $properties, $permission, array $options=array())
	{
		$folder = WdCore::getConfig('repository.temp');

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

		global $document;

		$document->addStyleSheet('public/edit.css');

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
			$uploaded_path = WdCore::getConfig('repository.temp') . '/' . basename($file->location) . $file->extension;

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

				WdElement::T_CHILDREN => array
				(
					File::PATH => new $uploader_class
					(
						array
						(
							WdForm::T_LABEL => 'File',
							WdElement::T_MANDATORY => empty($entry_nid),
							WdElement::T_FILE_WITH_LIMIT => true,
							WdElement::T_WEIGHT => -100,
							WdElement::T_GROUP => 'node'
						)
					),

					File::DESCRIPTION => new WdElement
					(
						'textarea', array
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
				WdManager::T_COLUMNS_ORDER => array('title', 'mime', 'size', 'uid', 'modified')
			)
		);
	}
}