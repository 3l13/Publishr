<?php

class resources_files_WdManager extends system_nodes_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct($module, $tags);

		global $document;

		$document->addStyleSheet('public/manage.css');
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
		$url = WdOperation::encode
		(
			$this->module, resources_files_WdModule::OPERATION_DOWNLOAD, array
			(
				'nid' => $entry->nid
			),

			true
		);

		$rc  = '<a class="download" href="' . wd_entities($url) . '"';
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