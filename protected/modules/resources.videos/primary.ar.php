<?php

class resources_videos_WdActiveRecord extends resources_files_WdActiveRecord
{
	const WIDTH = 'width';
	const HEIGHT = 'height';
	const DURATION = 'duration';
	const POSTER = 'poster';

	protected function model($name='resources.videos')
	{
		return parent::model($name);
	}
}