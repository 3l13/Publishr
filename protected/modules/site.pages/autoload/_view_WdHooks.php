<?php

/**
 * This file is part of the WdCore framework
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdcore/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdcore/license/
 */

class site_pages_view_WdHooks
{
	static private $pages_model;
	static protected $url_cache = array();

	static public function url(system_nodes_WdActiveRecord $ar, $type='view')
	{
		if (self::$pages_model === false)
		{
			#
			# we were not able to get the "site.pages" model in a previous call, we don't try again.
			#

			return '#';
		}
		else
		{
			try
			{
				global $core;

				self::$pages_model = $core->models['site.pages'];
			}
			catch (Exception $e)
			{
				return '#';
			}
		}

		# -15 is for "_WdActiveRecord"

		$constructor = isset($ar->constructor) ? $ar->constructor : substr(get_class($ar), 0, -15);
		$constructor = strtr($constructor, '.', '_');

		$key = 'views.targets.' . $constructor . '/' . $type;

		if (!isset(self::$url_cache[$key]))
		{
			global $registry;

			$page_id = $registry[$key];
			$page = self::$pages_model->load($page_id);

			self::$url_cache[$key] = $page ? $page->translation->url_pattern : false;
		}

		$pattern = self::$url_cache[$key];

		if (!$pattern)
		{
			return '#uknown-url-' . $type . '-for-' . $constructor;
		}

		return WdRoute::format($pattern, $ar);
	}

	/**
	 * Return the URL type 'view' for the node.
	 *
	 * @param system_nodes_WdActiveRecord $ar
	 */

	static public function get_url(system_nodes_WdActiveRecord $ar)
	{
		return self::url($ar);
	}

	/**
	 * Return the absolute URL type for the node.
	 *
	 * @param string $type The URL type.
	 *
	 */

	static public function absolute_url(system_nodes_WdActiveRecord $ar, $type='view')
	{
		return 'http://' . $_SERVER['HTTP_HOST'] . self::url($ar, $type);
	}

	/**
	 * Return the _primary_ absolute URL for the node.
	 *
	 * @return string The primary absolute URL for the node.
	 */

	static public function get_absolute_url(system_nodes_WdActiveRecord $ar)
	{
		return self::absolute_url($ar);
	}
}