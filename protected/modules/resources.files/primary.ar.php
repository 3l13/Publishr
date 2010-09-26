<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_files_WdActiveRecord extends system_nodes_WdActiveRecord
{
	const PATH = 'path';
	const MIME = 'mime';
	const SIZE = 'size';
	const DESCRIPTION = 'description';

	protected function __get_extension()
	{
		$path = $this->path;

		return substr($path, strrpos($path, '.'));
	}

	protected function __get_download_url()
	{
		/*
		return WdOperation::encode
		(
			$this->constructor, 'download', array
			(

			),

			'r', $this->nid
		);
		*/

		return '/do/' . $this->constructor . '/' . $this->nid . '/download';
	}

	public function url($type='view')
	{
		if ($type == 'download')
		{
			return $this->download_url;
		}

		return parent::url($type);
	}
}