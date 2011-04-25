<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

	protected function block_manage()
	{
		return new resources_images_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'is_online', 'uid', 'surface', 'size', 'modified'
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
							WdForm::T_LABEL => '.alt'
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
		$img = new WdElement
		(
			'img', array
			(
				'src' => $entry->thumbnail('$icon'),
				'alt' => '',
				'width' => self::ICON_WIDTH,
				'height' => self::ICON_HEIGHT
			)
		);

		$rc = $img . ' ' . parent::adjust_createEntry($entry);

		$path = wd_entities($entry->path);

		// TODO-20110108: use a dataset

		$rc .= '<input type="hidden" class="preview" value="' . $path . '" />';
		$rc .= '<input type="hidden" class="path" value="' . $path . '" />';

		return $rc;
	}

	protected function block_adjust(array $params)
	{
		return new WdAdjustImageWidget
		(
			array
			(
				WdAdjustImageWidget::T_CONSTRUCTOR => $this->id,
				WdElement::T_DESCRIPTION => null,

				'value' => isset($params['value']) ? $params['value'] : null
			)
		);
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

		$scope_value = $core->site->metas[$this->flat_id . '.property_scope'];

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
							WdForm::T_LABEL => '.property_scope',
							WdElement::T_OPTIONS => $scopes,

							'class' => 'checkbox-group list combo',
							'value' => $scope_value
						)
					)
				)
			)
		);
	}
}

class resources_images_adjustimage_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return '#' . $n;
	}
}