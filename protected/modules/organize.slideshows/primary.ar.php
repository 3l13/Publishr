<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class organize_slideshows_WdActiveRecord extends organize_lists_WdActiveRecord
{
	/**
	 * Returns the poster of the slideshow.
	 *
	 * If there is no poster defined for the slideshow, the first image of the slideshow is used
	 * instead.
	 *
	 * @return object An instance of the resources_images_WdActiveRecord class.
	 */

	protected function __get_poster()
	{
		if ($this->posterid)
		{
			global $core;

			return $core->models['resources.images'][$this->posterid];
		}

		$nodes = $this->nodes;

		if (!$nodes)
		{
			return;
		}

		return array_shift($nodes);
	}
}