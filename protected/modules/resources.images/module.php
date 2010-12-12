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
		global $core;

		$params = &$operation->params;

		$key = $this->flat_id . '.property_scope';
		$scope = null;

		if (isset($params['local'][$key]))
		{
			$scope = implode(',', array_keys($params['local'][$key]));

			unset($params['local'][$key]);
		}

		$core->working_site->metas[$key] = $scope;

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

	protected function block_edit(array $properties, $permission, array $options=array())
	{
		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission, $options), array
			(
				WdElement::T_CHILDREN => array
				(
					'alt' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Texte alternatif'
						)
					)
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

	protected function block_config()
	{
		global $core, $registry;

		if ($this->id != 'resources.images')
		{
			return parent::block_config();
		}

		$scopes = array();

		foreach ($core->descriptors as $module_id => $descriptor)
		{
			if (empty($descriptor[self::T_MODELS]['primary']))
			{
				continue;
			}

			if (!$core->has_module($module_id) || $module_id == $this->id)
			{
				continue;
			}

			$model = $descriptor[self::T_MODELS]['primary'];

			$is_instance = WdModel::is_extending($model, 'system.nodes');

			if (!$is_instance)
			{
				continue;
			}

			$module_id = strtr($module_id, '.', '_');

			$scopes[$module_id] = t($descriptor[self::T_TITLE]);
		}

		asort($scopes);

		$scope_key = $this->flat_id . '.property_scope';
		$scope_value = $core->working_site->metas[$scope_key];

		if ($scope_value)
		{
			$scope_value = explode(',', $scope_value);
			$scope_value = array_combine($scope_value, array_fill(0, count($scope_value), true));
		}

		#
		#
		#

		return wd_array_merge_recursive
		(
			parent::block_config(), array
			(
				WdElement::T_CHILDREN => array
				(
					"local[$this->flat_id.property_scope]" => new WdElement
					(
						WdElement::E_CHECKBOX_GROUP, array
						(
							WdForm::T_LABEL => "Permettre l'attachement d'une image aux entrées des modules suivants",
							WdElement::T_OPTIONS => $scopes,

							'class' => 'checkbox-group list combo',
							'value' => $scope_value
						)
					)
				)
			)
		);
	}

	public function event_operation_save(WdEvent $event)
	{
		$operation = $event->operation;
		$params = &$operation->params;

		if (!isset($params['resources_images']['imageid']))
		{
			return;
		}

		// FIXME-20100817: this will fail with new entries !

		$entry = $operation->entry;
		$imageid = $params['resources_images']['imageid'];

		$entry->metas['resources_images.imageid'] = $imageid ? $imageid : null;
	}

	public function ar_get_image(system_nodes_WdActiveRecord $ar)
	{
		$imageid = $ar->metas['resources_images.imageid'];

		// TODO-20100817: default image

		return $imageid ? $this->model()->load($imageid) : null;
	}
}

class resources_images_adjustimage_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return '#' . $n;
	}
}