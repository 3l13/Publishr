<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_videos_WdActiveRecord extends resources_files_WdActiveRecord
{
	const WIDTH = 'width';
	const HEIGHT = 'height';
	const DURATION = 'duration';
	const POSTERID = 'posterid';

	public $width;
	public $height;
	public $duration;
	public $posterid;

	protected function __get_poster()
	{
		global $core;

		if (!$this->posterid)
		{
			return;
		}

		return $core->models['resources.images'][$this->posterid];
	}
}