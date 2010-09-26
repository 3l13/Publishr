<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class organize_slideshows_WdModule extends organize_lists_WdModule
{
	// TODO-20100531: this is a dirty solution, but currently submodels are not inherited

	public function model($name='primary')
	{
		if ($name == 'nodes')
		{
			global $core;

			return $core->getModule('organize.lists')->model('nodes');
		}

		return parent::model($name);
	}

	protected function block_manage()
	{
		return new organize_slideshows_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'uid', 'is_online', 'modified'
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		$properties['scope'] = 'resources.images';

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdForm::T_HIDDENS => array
				(
					'scope' => $properties['scope']
				),

				WdElement::T_CHILDREN => array
				(
					'scope' => null,

					'posterid' => new WdPopImageElement
					(
						array
						(
							WdForm::T_LABEL => 'Poster',
							WdElement::T_DESCRIPTION => "Le poster est utilisé pour réprésenter
							le diaporama. Par défaut, la première image du diaporama est utilisée."
						)
					)
				)
			)
		);
	}

	public function adjust_createEntry($entry)
	{
		global $registry;

		$rc = '';

		$w = $registry->get('thumbnailer.versions.$icon.w');
		$h = $registry->get('thumbnailer.versions.$icon.h');

		$poster = $entry->poster;

		if ($poster)
		{
			$img = new WdElement
			(
				'img', array
				(
					'src' => WdOperation::encode
					(
						'thumbnailer', 'get', array
						(
							'src' => $poster->path,
							'version' => '$icon'
						)
					),

					'width' => $w,
					'height' => $h,

					'alt' => ''
				)
			);

			$rc .= $img . ' ';
		}

		$rc .= parent::adjust_createEntry($entry);

		if ($poster)
		{
			$rc .= '<input type="hidden" class="preview" value="' . wd_entities($poster->path) . '" />';
			$rc .= '<input type="hidden" class="path" value="' . wd_entities($poster->path) . '" />';
		}

		return $rc;
	}
}