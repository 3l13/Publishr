<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class thumbnailer_WdHooks
{
	/**
	 * Callback for the `thumbnail` getter added to the active records of the "resources.images"
	 * module.
	 *
	 * The thumbnail is created using options of the 'primary' version.
	 *
	 * @param resources_images_WdActiveRecord $ar An active record of the "resources.images"
	 * module.
	 * @return string The URL of the thumbnail.
	 */

	static public function object_get_thumbnail(resources_images_WdActiveRecord $ar)
	{
		return self::thumbnail($ar, 'primary');
	}

	/**
	 * Callback for the `thumbnail()` method added to the active records of the "resources.images"
	 * module.
	 *
	 * @param resources_images_WdActiveRecord $ar An active record of the "resources.images"
	 * module.
	 * @param string $version The version used to create the thumbnail, or a number of options
	 * defined as CSS properties. e.g. 'w:300;h=200'.
	 * @return string The URL of the thumbnail.
	 */

	static public function object_thumbnail(resources_images_WdActiveRecord $ar, $version)
	{
		$base = '/api/' . $ar->constructor . '/' . $ar->nid . '/thumbnail?';

		if (strpos($version, ':') !== false)
		{
			$args = self::parse_style($version);

			return $base . http_build_query($args, null, '&');
		}

		return $base . 'version=' . $version;
	}

	static private function parse_style($style)
	{
		preg_match_all('#([^:]+):\s*([^;]+);?#', $style, $matches, PREG_PATTERN_ORDER);

		return array_combine($matches[1], $matches[2]);
	}

	/**
	 * Callback for the `alter.block.config` event, adding AdjustThumbnail elements to the
	 * `config` block if image versions are defined for the constructor.
	 *
	 * @param WdEvent $ev
	 */

	static public function event_alter_block_config(WdEvent $ev)
	{
		$module_id = (string) $ev->module;

		$c = WdConfig::get_constructed('thumbnailer', 'merge');

		$configs = array();

		foreach ($c as $version_name => $config)
		{
			if (empty($config['module']) || $config['module'] != $module_id)
			{
				continue;
			}

			$configs[$version_name] = $config;
		}

		if (!$configs)
		{
			return;
		}

		$children = array();

		foreach ($configs as $version_name => $config)
		{
			list($defaults) = $config;

			$config += array
			(
				'description' => null
			);

			$children['global[thumbnailer.versions][' . $version_name . ']'] = new WdAdjustThumbnailElement
			(
				array
				(
					WdForm::T_LABEL => $config['title'] . ' <small>(' . $version_name . ')</small>',
					WdElement::T_DEFAULT => $defaults,
					WdElement::T_GROUP => 'thumbnailer',
					WdElement::T_DESCRIPTION => $config['description']
				)
			);
		}

		$ev->tags = wd_array_merge_recursive
		(
			$ev->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'thumbnailer' => array
					(
						'title' => 'Miniatures',
						'class' => 'form-section flat',
						'description' => "Ce groupe permet de configurer les différentes
						versions de miniatures qu'il est possible d'utiliser pour
						les entrées de ce module."
					)
				),

				WdElement::T_CHILDREN => $children
			)
		);
	}

	/**
	 * Callback for the `config:before` event, pre-parsing thumbnailer versions if they are
	 * defined.
	 *
	 * @param WdEvent $ev
	 */

	static public function event_operation_config_before(WdEvent $ev)
	{
		$params = &$ev->operation->params;

		if (empty($params['global']['thumbnailer.versions']))
		{
			return;
		}

		$c = WdConfig::get_constructed('thumbnailer', 'merge');

		foreach ($params['global']['thumbnailer.versions'] as $name => &$version)
		{
			$version += $c[$name][0] + array
			(
				'no-upscale' => false,
				'interlace' => false
			);

			$version['no-upscale'] = filter_var($version['no-upscale'], FILTER_VALIDATE_BOOLEAN);
			$version['interlace'] = filter_var($version['interlace'], FILTER_VALIDATE_BOOLEAN);
		}
	}
}