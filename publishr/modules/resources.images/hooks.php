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

		$flat_id = $event->target->flat_id;
		$inject = $core->registry['resources_images.inject.' . $flat_id];

		if (!$inject)
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
							WdElement::T_REQUIRED => $core->registry['resources_images.inject.' . $flat_id . '.required'],

							'value' => $imageid
						)
					)
				)
			)
		);
	}

	static public function alter_block_config(WdEvent $event)
	{
		global $core;

		$core->document->css->add('public/admin.css');
		$core->document->js->add('public/admin.js');

		$module = $event->module;
		$module_flat_id = $module->flat_id;

		$views = array
		(
			$module . '/home' => array
			(
				'title' => 'Accueil des enregistrements'
			),

			$module . '/list' => array
			(
				'title' => 'Liste des enregistrements'
			),

			$module . '/view' => array
			(
				'title' => "Detail d'un enregistrement"
			)
		);

		$thumbnails = array();

		foreach ($views as $view_id => $view)
		{
			$id = wd_normalize($view_id);

			$thumbnails["global[thumbnailer.versions][$id]"] = new WdAdjustThumbnailConfigElement
			(
				array
				(
					WdElement::T_GROUP => 'resources_images__inject_thumbnails',
					WdForm::T_LABEL => $view['title'] . ' <span class="small">(' . $id . ')</span>'
				)
			);
		}

		/*
		$target_module = $event->target;
		$target_module_flat_id = $target_module->flat_id;

		var_dump($core->site->metas->model->select('name, value')->where('name LIKE "views.targets.%"')->pairs);
		*/

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'resources_images__inject' => array
					(
						'title' => 'Associated image',
						'class' => 'form-section flat'
					),

					'resources_images__inject_options' => array
					(
						'class' => 'form-section flat'
					),

					'resources_images__inject_thumbnails' => array
					(
						'description' => 'Use the following elements to configure the
						thumbnails to create for the associated image. Each view provided by the
						module has its own thumbnail configuration:',
						'class' => 'form-section flat'
					)
				),

				WdElement::T_CHILDREN => array
				(
					'global[resources_images.inject][' . $module_flat_id . ']' => new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => 'Associer une image aux enregistrements',
							WdElement::T_GROUP => 'resources_images__inject'
						)
					),

					'global[resources_images.inject][' . $module_flat_id . '.required]' => new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => "L'association est obligatoire",
							WdElement::T_GROUP => 'resources_images__inject_options'
						)
					),

					'global[resources_images.inject][' . $module_flat_id . '.default]' => new WdPopImageWidget
					(
						array
						(
							WdForm::T_LABEL => "Image par défaut",
							WdElement::T_GROUP => 'resources_images__inject_options'
						)
					)
				)

				+ $thumbnails
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

	static public function before_operation_config(WdEvent $event)
	{
		if (!isset($event->operation->params['global']['resources_images.inject']))
		{
			return;
		}

		$module_flat_id = $event->target->flat_id;
		$options = &$event->operation->params['global']['resources_images.inject'];

		$options += array
		(
			$module_flat_id => false,
			$module_flat_id . '.required' => false,
			$module_flat_id . '.default' => null
		);

		$options[$module_flat_id] = filter_var($options[$module_flat_id], FILTER_VALIDATE_BOOLEAN);
		$options[$module_flat_id . '.required'] = filter_var($options[$module_flat_id . '.required'], FILTER_VALIDATE_BOOLEAN);
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