<?php

class resources_videos_WdModule extends resources_files_WdModule
{
	protected $accept = array
	(
		'video/x-flv'
	);

	protected $uploader_class = 'WdVideoUploadElement';

	/*
	protected function validate_operation_save(WdOperation $operation)
	{
		$poster = new WdUploaded(Video::POSTER, array('image/jpeg'));

		if ($poster->er)
		{
			$operation->form->log(Video::POSTER, $poster->er_message);

			return false;
		}

		$operation->poster = $poster;

		return parent::validate_operation_save($operation);
	}

	protected function operation_save(WdOperation $operation)
	{
		$poster = $operation->poster;

		if ($poster->location)
		{
			$root = $_SERVER['DOCUMENT_ROOT'];
			$destination = WdCore::getConfig('repository.temp') . '/' . basename($poster->location) . $poster->extension;

			$poster->move($root . $destination);

			$operation->params[Video::POSTER] = $destination;
		}

		wd_log('poster: \1, \2', array($poster, $operation->params));

		return parent::operation_save($operation);
	}
	*/

	/*
	protected function block_edit(array $properties, $permission, array $options=array())
	{
		return wd_array_merge_recursive
		(
			parent::block_edit
			(
				$properties, $permission, $options
			),

			array
			(
				WdElement::T_CHILDREN => array
				(
					'poster' => new WdElement
					(
						WdElement::E_FILE, array
						(
							WdForm::T_LABEL => 'Poster',
							WdElement::T_WEIGHT => 110,
							WdElement::T_FILE_WITH_REMINDER => true
						)
					)
				)
			)
		);
	}
	*/

	protected function block_manage()
	{
		return new resources_videos_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'poster', 'title', 'url', 'surface', 'duration', 'uid', 'modified', 'is_online'
				)
			)
		);
	}
}