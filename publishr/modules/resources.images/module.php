<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
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
		global $core;

		$registry = $core->registry;

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

	protected function operation_upload(WdOperation $operation)
	{
		$rc = parent::operation_upload($operation);

		if ($operation->response->infos)
		{
			$file = $operation->file;
			$path = $file->location;
			$size = wd_format_size($file->size);

			// TODO-20110106: compute surface w & h and use them for img in order to avoid poping

			$operation->response->infos = '<div class="preview">'

			.

			new WdElement
			(
				'img', array
				(
					'src' => WdOperation::encode
					(
						'thumbnailer', 'get', array
						(
							'src' => $path,
							'w' => 64,
							'h' => 64,
							'format' => 'png',
							'background' => 'silver,white,medium',
							'm' => 'surface',
							'uniqid' => uniqid()
						)
					),

					'alt' => ''
				)
			)

			. '</div>' . $operation->response->infos;
		}

		return $rc;
	}

	protected function controls_for_operation_get(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_RECORD => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_get(WdOperation $operation)
	{
		global $core;

		try
		{
			$thumbnailer = $core->modules['thumbnailer'];

			$record = $operation->record;
			$params = &$operation->params;

			$params['src'] = $record->path;

			if (empty($params['version']) && empty($params['v']))
			{
				$operation->params += array
				(
					'w' => $record->width,
					'h' => $record->height
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
		global $core;

		if ($this->id != 'resources.images')
		{
			return parent::block_config();
		}

		$scopes = array();

		foreach ($core->modules->descriptors as $module_id => $descriptor)
		{
			if (empty($core->modules[$module_id]) || $module_id == $this->id || $module_id == 'system.nodes' || $module_id == 'contents')
			{
				continue;
			}

			$is_instance = WdModule::is_extending($module_id, 'system.nodes');

			if (!$is_instance)
			{
				continue;
			}

			$module_id = strtr($module_id, '.', '_');

			$scopes[$module_id] = t($descriptor[self::T_TITLE]);
		}

		asort($scopes);

		$scope_value = $core->working_site->metas[$this->flat_id . '.property_scope'];

		if ($scope_value)
		{
			$scope_value = explode(',', $scope_value);
			$scope_value = array_combine($scope_value, array_fill(0, count($scope_value), true));
		}

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

		$entry = $event->target->model[$event->rc['key']];
		$imageid = $params['resources_images']['imageid'];

		$entry->metas['resources_images.imageid'] = $imageid ? $imageid : null;
	}

	public function ar_get_image(system_nodes_WdActiveRecord $ar)
	{
		$imageid = $ar->metas['resources_images.imageid'];

		// TODO-20100817: default image

		return $imageid ? $this->model[$imageid] : null;
	}
}

class resources_images_adjustimage_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return '#' . $n;
	}
}