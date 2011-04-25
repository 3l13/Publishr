<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
					'class' => 'size'
				)
			)
		);
	}

	protected function render_cell_title(system_nodes_WdActiveRecord $record, $property)
	{
		$rc  = '<a class="download" href="' . wd_entities($record->url('download')) . '"';
		$rc .= ' title="' . t('Download the file: :path', array(':path' => $record->path)) . '"';
		$rc .= '>';
		$rc .= 'download</a>';

		$rc .= parent::render_cell_title($record, $property);

		return $rc;
	}

	protected function render_cell_mime($record, $property)
	{
		return parent::render_filter_cell($record, $property);
	}
}