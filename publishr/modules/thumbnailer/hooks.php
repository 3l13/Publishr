<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
		$base = '/api/' . $ar->constructor . '/' . $ar->nid . '/thumbnail';

		if (strpos($version, ':') !== false)
		{
			$args = self::parse_style($version);

			return $base . '?' . http_build_query($args, null, '&');
		}

		return $base . 's/' . $version;
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
	static public function alter_block_config(WdEvent $ev)
	{
		global $core;

		$module_id = (string) $ev->module;

		$c = $core->configs->synthesize('thumbnailer', 'merge');

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

			$children['global[thumbnailer.versions][' . $version_name . ']'] = new WdAdjustThumbnailConfigElement
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
	 * Callback for the `properties:before` event, pre-parsing thumbnailer versions if they are
	 * defined.
	 *
	 * @param WdEvent $ev
	 */
	static public function event_before_config_properties(WdEvent $event)
	{
		global $core;

		$properties = &$event->properties;

		if (empty($properties['global']['thumbnailer.versions']))
		{
			return;
		}

		$config = $core->configs->synthesize('thumbnailer', 'merge');

		foreach ($properties['global']['thumbnailer.versions'] as $name => &$options)
		{
			$options += (isset($config[$name][0]) ? $config[$name][0] : array()) + array
			(
				'no-upscale' => false,
				'interlace' => false
			);

			$options['no-upscale'] = filter_var($options['no-upscale'], FILTER_VALIDATE_BOOLEAN);
			$options['interlace'] = filter_var($options['interlace'], FILTER_VALIDATE_BOOLEAN);

			$options = (empty($options['w']) && empty($options['h'])) ? null : json_encode($options);
		}
	}

	/*
	 * SYSTEM.CACHE SUPPORT
	 */

	static public function alter_block_manage(WdEvent $event)
	{
		global $core;

		$event->caches['thumbnails'] = array
		(
			'title' => 'Miniatures',
			'description' => "Miniatures générées à la volée par le module <q>Thumbnailer</q>.",
			'group' => 'resources',
			'state' => null,
			'size_limit' => array(4, 'Mo'),
			'time_limit' => array(7, 'Jours')
		);
	}

	static public function stat_cache(system_cache__stat_WdOperation $operation)
	{
		global $core;

		$path = $core->config['repository.cache'] . '/thumbnailer';

		return $operation->get_files_stat($path);
	}

	static public function clear_cache(system_cache__clear_WdOperation $operation)
	{
		global $core;

		$path = $core->config['repository.cache'] . '/thumbnailer';

		$files = glob($_SERVER['DOCUMENT_ROOT'] . $path . '/*');

		foreach ($files as $file)
		{
			unlink($file);
		}

		return count($files);
	}
}