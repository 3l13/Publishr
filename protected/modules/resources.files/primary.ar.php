<?php

class resources_files_WdActiveRecord extends system_nodes_WdActiveRecord
{
	const PATH = 'path';
	const MIME = 'mime';
	const SIZE = 'size';
	const DESCRIPTION = 'description';

	public function url($type='view')
	{
		if ($type == 'download')
		{
			return WdOperation::encode
			(
				$this->constructor, 'download', array
				(
					'nid' => $this->nid
				),

				true
			);
		}

		return parent::url($type);
	}
}