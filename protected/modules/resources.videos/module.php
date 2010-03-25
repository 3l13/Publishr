<?php

class resources_videos_WdModule extends resources_files_WdModule
{
	protected $accept = array
	(
		'video/x-flv'
	);

	protected $uploader_class = 'WdVideoUploadElement';

	protected function block_manage()
	{
		return new resources_videos_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'poster', 'title', 'surface', 'size', 'duration', 'uid', 'modified', 'is_online'
				)
			)
		);
	}
}