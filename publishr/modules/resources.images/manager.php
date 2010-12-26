<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_images_WdManager extends resources_files_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct($module, $tags);

		global $document;

		$document->js->add('public/slimbox.js');
		$document->css->add('public/slimbox.css');

		$document->js->add('public/manage.js');
		$document->css->add('public/manage.css');
	}

	protected function columns()
	{
		$columns = parent::columns() + array
		(
			'surface' => array
			(
				self::COLUMN_LABEL => 'Dimensions',
				self::COLUMN_CLASS => 'size'
			)
		);

		$columns['title'][self::COLUMN_CLASS] = 'thumbnail';

		return $columns;
	}

	protected function get_cell_title($entry, $tag)
	{
		$path = $entry->path;

		$rc  = '<a href="' . wd_entities($path) . '" rel="lightbox[]">';
		$rc .= '<img alt="' . basename($path) . '"';
		$rc .= ' src="' . wd_entities($entry->thumbnail('$icon')) . '"';
		$rc .= ' width="' .  resources_images_WdModule::ICON_WIDTH . '"';
		$rc .= ' height="' . resources_images_WdModule::ICON_HEIGHT . '"';
		$rc .= ' />';
		$rc .= '<input type="hidden" value="' . wd_entities($entry->thumbnail('$popup')) . '" />';
		$rc .= '</a>';

		$rc .= parent::get_cell_title($entry, $tag);

		return $rc;
	}

	protected function get_cell_surface($entry)
	{
		return $entry->width . '&times;' . $entry->height . '&nbsp;px';
	}
}