<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class resources_images_WdHooks
{
	/**
	 * Getter for the mixin magic property `image`
	 *
	 * @param system_nodes_WdActiveRecord $ar
	 * @return resources_images_WdActiveRecord|null
	 */
	static public function __get_image(system_nodes_WdActiveRecord $ar)
	{
		global $core;

		$imageid = $ar->metas['resources_images.imageid'];

		return $imageid ? $core->models['resources.images'][$imageid] : null;
	}

	static public function alter_block_edit(WdEvent $event)
	{
		global $core;

		$scope = $core->working_site->metas['resources_images.property_scope'];

		if (!$scope)
		{
			return;
		}

		$scope = explode(',', $scope);

		if (!in_array($event->target->flat_id, $scope))
		{
			return;
		}

		$group = null;

		if (isset($event->tags[WdElement::T_GROUPS]['contents']))
		{
			$group = 'contents';
		}

		$imageid = null;

		if ($event->entry)
		{
			$imageid = $event->entry->metas['resources_images.imageid'];
		}

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_CHILDREN => array
				(
					'resources_images[imageid]' => new WdPopImageWidget
					(
						array
						(
							WdForm::T_LABEL => 'Image',
							WdElement::T_GROUP => $group,

							'value' => $imageid
						)
					)
				)
			)
		);
	}

	static public function operation_save(WdEvent $event)
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

	static public function textmark_images_reference(array $args, Textmark_Parser $textmark, array $matches)
	{
		static $model;

		if (!$model)
		{
			global $core;

			$model = $core->models['resources.images'];
		}

		$align = $matches[2];
		$alt = $matches[3];
		$id = $matches[4];

		# for shortcut links like ![this][].

		if (!$id)
		{
			$id = $alt;
		}

		$entry = $model->where('nid = ? OR slug = ? OR title = ?', (int) $id, $id, $id)->order('created DESC')->one;

		if (!$entry)
		{
			$matches[2] = $matches[3];
			$matches[3] = $matches[4];

			WdDebug::trigger('should call standard one !');

			//return parent::_doImages_reference_callback($matches);

			return;
		}

		$src = $entry->path;
		$w = $entry->width;
		$h = $entry->height;

		$light_src = null;

		if ($w > 600)
		{
			$w = 600;
			$h = null;

			$light_src = $src;

			$src = '/api/resources.images/' . $entry->nid . '/thumbnail?' . http_build_query
			(
				array
				(
					'w' => $w,
					'method' => 'fixed-width',
					'quality' => 80
				)
			);
		}

		$params = array
		(
			'src' => $src,
			'alt' => $alt,
			'width' => $w,
			'height' => $h
		);

		if ($align)
		{
			switch ($align)
			{
				case '<': $align = 'left'; break;
				case '=':
				case '|': $align = 'middle'; break;
				case '>': $align = 'right'; break;
			}

			$params['align'] = $align;
		}

		$rc = new WdElement('img', $params);

		if ($light_src)
		{
			$rc = '<a href="' . $light_src . '" rel="lightbox[]">' . $rc . '</a>';
		}

		return $rc;
	}

	/**
	 * Adds assets to support lightbox links.
	 *
	 * This function is a callback for the `publishr.publish` event.
	 *
	 * @param WdEvent $event
	 */

	static public function publishr_publish(WdEvent $event)
	{
		global $document;

		if (strpos($event->rc, 'rel="lightbox') === false)
		{
			return;
		}

		$document->css->add('public/slimbox.css');
		$document->js->add('public/slimbox.js');
	}
}