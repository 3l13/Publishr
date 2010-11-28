<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class organize_slideshows_list_WdMarkup extends system_nodes_list_WdMarkup
{
	protected $constructor = 'organize.slideshows';
}

class organize_slideshows_home_WdMarkup extends organize_slideshows_list_WdMarkup
{


	/*
	static public function home(array $args, WdPatron $patron, $template)
	{
		$entries = self::model()->loadRange(0, 4, 'ORDER BY created DESC')->fetchAll();

		return $patron->publish($template, $entries);
	}
	*/
}