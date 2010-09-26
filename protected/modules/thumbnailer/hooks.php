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
	static public function get_thumbnail(resources_images_WdActiveRecord $ar)
	{
		return self::thumbnail($ar);
	}

	static public function get_thumbnail_url(resources_images_WdActiveRecord $ar)
	{
		WdDebug::trigger('Use the parameterized thumbnail() method instead');

		return '/do/' . $ar->constructor . '/' . $ar->nid . '/thumbnail';
	}

	static public function thumbnail(resources_images_WdActiveRecord $ar, $version='view')
	{
		if (strpos($version, ':') !== false)
		{
			$args = wd_parse_style($version);

			return '/do/' . $ar->constructor . '/' . $ar->nid . '/thumbnail?' . http_build_query
			(
				$args, null, '&'
			);
		}



		/*
		global $registry;

		if ($version[0] == '/')
		{
			$version = strtr($ar->constructor, '.', '_') . $version;
		}

		$config = $registry['thumbnailer.versions.' . $version . '.'];

		if (!$config)
		{
			return '#unknown-thumbnail-version-' . $version;
		}

		$path = isset($config['path']) ? $config['path'] : null;
		$src = $ar->path;

		if ($path && strpos($src, $path) === 0)
		{
			$src = substr($src, strlen($path));
		}

		return WdOperation::encode
		(
			'thumbnailer', 'get', array
			(
				'src' => $src,
				'version' => $version
			),

			'r'
		);
		*/

		return '/do/' . $ar->constructor . '/' . $ar->nid . '/thumbnail?version=' . $version;
	}
}

function wd_parse_style($style)
{
	preg_match_all('#([a-z\-]+)\:\s*([^;]+)#', $style, $matches, PREG_PATTERN_ORDER);

	return array_combine($matches[1], $matches[2]);
}