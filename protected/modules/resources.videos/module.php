<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_videos_WdModule extends resources_files_WdModule
{
	protected $accept = array
	(
		'video/x-flv'
	);

	protected $uploader_class = 'WdVideoUploadElement';

	protected function block_manage()
	{
		return new resources_videos_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'poster', 'title', 'surface', 'size', 'duration', 'uid', 'modified', 'is_online'
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission, array $options=array())
	{
		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_CHILDREN => array
				(
					'posterid' => new WdPopImageElement
					(
						array
						(
							WdForm::T_LABEL => 'Poster'
						)
					)
				)
			)
		);
	}

	public function adjust_createEntry($entry)
	{
		global $registry;

		$rc = parent::adjust_createEntry($entry);

		if ($entry->poster)
		{
			$w = $registry->get('thumbnailer.versions.$icon.w');
			$h = $registry->get('thumbnailer.versions.$icon.h');

			$img = new WdElement
			(
				'img', array
				(
					'src' => WdOperation::encode
					(
						'thumbnailer', 'get', array
						(
							'src' => $entry->poster->path,
							'version' => '$icon'
						)
					),

					'width' => $w,
					'height' => $h,

					'alt' => ''
				)
			);

			$rc = $img . ' ' . $rc;

			$rc .= '<input type="hidden" class="path" value="' . wd_entities($entry->poster->path) . '" />';
		}

		return $rc;
	}
}