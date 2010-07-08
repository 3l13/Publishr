<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_images_WdModule extends resources_files_WdModule
{
	const ICON_WIDTH = 24;
	const ICON_HEIGHT = 24;
	const THUMBNAIL_WIDTH = 200;
	const THUMBNAIL_HEIGHT = 200;

	protected $accept = array
	(
		'image/gif', 'image/png', 'image/jpeg'
	);

	protected $uploader_class = 'WdImageUploadElement';

	public function install()
	{
		global $registry;

		#
		# we use 'resources.images' instead of 'this' to avoid problems with inheritence
		#

		$registry->set
		(
			'thumbnailer.versions.$icon', array
			(
				'w' => self::ICON_WIDTH,
				'h' => self::ICON_HEIGHT,
				'format' => 'png'
			)
		);

		$registry->set
		(
			'thumbnailer.versions.$popup', array
			(
				'w' => self::THUMBNAIL_WIDTH,
				'h' => self::THUMBNAIL_HEIGHT,
				'method' => WdImage::RESIZE_SURFACE,
				'no-upscale' => true,
				'quality' => 90
			)
		);

		return parent::install();
	}

	protected function operation_config(WdOperation $operation)
	{
		$params = &$operation->params;

		#
		# handle booleans
		#

		foreach ($params['thumbnailer']['versions'] as $version => &$options)
		{
			$options += array
			(
				'no-upscale' => false,
				'interlace' => false
			);

			$options['no-upscale'] = filter_var($options['no-upscale'], FILTER_VALIDATE_BOOLEAN);
			$options['interlace'] = filter_var($options['interlace'], FILTER_VALIDATE_BOOLEAN);
		}

		return parent::operation_config($operation);
	}

	protected function validate_operation_get(WdOperation $operation)
	{
		return parent::validate_operation_download($operation);
	}

	protected function operation_get(WdOperation $operation)
	{
		global $core;

		try
		{
			$thumbnailer = $core->getModule('thumbnailer');

			$entry = $operation->entry;
			$params = &$operation->params;

			$params['src'] = $entry->path;

			if (empty($params['version']))
			{
				$operation->params += array
				(
					'w' => $entry->width,
					'h' => $entry->height
				);
			}

			$thumbnailer->handle_operation($operation);
		}
		catch (Exception $e) { }
	}

	protected function block_manage()
	{
		return new resources_images_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'surface', 'size', 'uid', 'is_online', 'modified'
				)
			)
		);
	}

	protected function block_gallery()
	{
		return new resources_images_WdManagerGallery
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array(File::TITLE, 'surface', File::SIZE, File::MODIFIED),
				WdManager::T_ORDER_BY => File::TITLE
			)
		);
	}

	/*
	protected function block_adjust($params)
	{
		return new WdAdjustImageElement
		(
			array
			(
				WdElement::T_DESCRIPTION => null,

				'value' => isset($params['value']) ? $params['value'] : null
			)
		);
	}
	*/

	public function adjust_createEntry($entry)
	{
		global $registry;

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
						'src' => $entry->path,
						'version' => '$icon'
					)
				),

				'width' => $w,
				'height' => $h,

				'alt' => ''
			)
		);

		$rc = $img . ' ' . parent::adjust_createEntry($entry);

		$rc .= '<input type="hidden" class="preview" value="' . wd_entities($entry->path) . '" />';
		$rc .= '<input type="hidden" class="path" value="' . wd_entities($entry->path) . '" />';

		return $rc;
	}
}

class resources_images_adjustimage_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return '#' . $n;
	}
}