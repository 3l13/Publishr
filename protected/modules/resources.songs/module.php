<?php

class resources_songs_WdModule extends resources_files_WdModule
{
	protected $accept = array
	(
		'audio/mpeg'
	);

	static public function parseMP3($file)
	{
		$reader = new WdMP3Reader();
		$reader->_read_v2($file);

		return array_intersect_key
		(
			get_object_vars($reader), array_flip
			(
				array('artist', 'album', 'track', 'title', 'year', 'duration', 'bitrate', 'rating')
			)
		);
	}

	protected function operation_upload(WdOperation $operation, array $options=array())
	{
		$id = parent::operation_upload($operation);

		if ($id)
		{
			$tags = self::parseMP3($operation->file->location);

			//echo strip_tags(wd_dump($tags));

			$_SESSION[self::SESSION_UPLOAD_RESPONSE][$id]['fields'] = $tags;

			/*
			$operation->response->tags = $tags;
			*/
		}

		return $id;
	}

	protected function validate_operation_load(WdOperation $operation)
	{
		$entry = $this->model()->load($operation->key);

		if (!$entry)
		{
			return false;
		}

		$operation->entry = $entry;

		return true;
	}

	protected function operation_load(WdOperation $operation)
	{
		return $operation->entry;
	}

	/*
	**

	BLOCKS

	**
	*/

	protected function block_edit(array $properties, $permission, array $options=array())
	{
		$tags = parent::block_edit
		(
			$properties, $permission, array
			(
				self::ACCEPT => $this->accept,
				self::UPLOADED => &$uploaded
			)
		);

		#
		# check uploaded file
		#

		$infos = array();

		if ($uploaded)
		{
			#
			# parse mp3
			#

			$infos = $this->parseMP3($uploaded->location);

			//wd_log('infos: \1\2', array($infos, $properties));
		}

		#
		# form
		#

		return wd_array_merge_recursive
		(
			$tags, array
			(
				WdForm::T_VALUES => $infos,

				WdElement::T_GROUPS => array
				(
					'mp3' => array
					(
					)
				),

				WdElement::T_CHILDREN => array
				(
					'artist' => new WdPElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Artist',
							WdElement::T_GROUP => 'mp3'
						)
					),

					'album' => new WdPElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Album',
							WdElement::T_GROUP => 'mp3'
						)
					),

					'year' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Year',
							WdElement::T_GROUP => 'mp3'
						)
					),

					'track' => new WdPElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Track',
							WdElement::T_GROUP => 'mp3'
						)
					)
				)
			)
		);
	}

	protected function block_manage()
	{
		return new resources_songs_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'artist', /*'album', 'year',*/ 'duration', 'size', 'uid', 'modified', 'is_online'
				)
			)
		);
	}
}