<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class organize_slideshows_WdManager extends organize_lists_WdManager
{
	public function __construct($module, $tags)
	{
		global $core;

		parent::__construct($module, $tags);

		$core->document->css->add('public/manage.css');
	}

	protected function get_cell_title(system_nodes_WdActiveRecord $record, $property)
	{
		$rc = '';
		$poster = $record->poster;

		if ($poster)
		{
			$rc = '<img src="' . wd_entities($poster->thumbnail('$icon')) . '" class="icon" alt="" /> ';
		}

		$rc .= '<div class="contents">' . parent::get_cell_title($record, $property) . '</div>';

		return $rc;
	}
}