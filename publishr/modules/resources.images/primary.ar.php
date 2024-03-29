<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_images_WdActiveRecord extends resources_files_WdActiveRecord
{
	const WIDTH = 'width';
	const HEIGHT = 'height';
	const ALT = 'alt';

	public $width;
	public $height;
	public $alt;

	public function __toString()
	{
		return '<img src="' . wd_entities($this->path) . '" alt="' . wd_entities($this->alt) . '" />';
	}
}