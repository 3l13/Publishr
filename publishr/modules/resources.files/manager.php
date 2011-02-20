<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_files_WdManager extends system_nodes_WdManager
{
	public function __construct($module, array $tags=array())
	{
		global $core;

		parent::__construct($module, $tags);

		$core->document->css->add('public/manage.css');
	}

	protected function columns()
	{
		return array_merge
		(
			parent::columns(), array
			(
				File::MIME => array
				(

				),

				File::SIZE => array
				(
					self::COLUMN_CLASS => 'size'
				)
			)
		);
	}

	protected function get_cell_title(system_nodes_WdActiveRecord $record, $property)
	{
		$rc  = '<a class="download" href="' . wd_entities($record->url('download')) . '"';
		$rc .= ' title="' . t('Download the file: :path', array(':path' => $record->path)) . '"';
		$rc .= '>';
		$rc .= 'download</a>';

		$rc .= parent::get_cell_title($record, $property, $this);

		return $rc;
	}

	protected function get_cell_mime($entry, $tag)
	{
		return parent::select_callback($entry, $tag, $this);
	}

	protected function get_cell_size($entry, $tag)
	{
		return parent::size_callback($entry, $tag);
	}
}