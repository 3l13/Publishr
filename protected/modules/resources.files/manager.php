<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_files_WdManager extends system_nodes_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct($module, $tags);

		global $document;

		$document->css->add('public/manage.css');
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

	protected function get_cell_title($entry, $tag)
	{
		$rc  = '<a class="download" href="' . wd_entities($entry->url('download')) . '"';
		$rc .= ' title="' . t('Download the file: :path', array(':path' => $entry->path)) . '"';
		$rc .= '>';
		$rc .= 'download</a>';

		$rc .= parent::get_cell_title($entry, $tag, $this);

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