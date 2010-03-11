<?php

class resources_images_WdActiveRecord extends resources_files_WdActiveRecord
{
	const WIDTH = 'width';
	const HEIGHT = 'height';

	protected function __get_thumbnail()
	{
		return $this->thumbnail('primary');
	}

	public function thumbnail($version)
	{
		return WdOperation::encode
		(
			'thumbnailer', 'get', array
			(
				'src' => $this->path,
				'version' => $version
			),

			true
		);
	}
}