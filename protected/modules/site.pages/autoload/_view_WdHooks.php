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
	static protected $url_cache_by_siteid = array();

	static public function url(system_nodes_WdActiveRecord $target, $type='view')
	{
		global $core;

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

		$constructor = isset($target->constructor) ? $target->constructor : substr(get_class($target), 0, -15);
		$constructor = strtr($constructor, '.', '_');

		$siteid = $target->siteid;
		$key = 'views.targets.' . $constructor . '/' . $type;

		if (!isset(self::$url_cache_by_siteid[$siteid][$key]))
		{
			$site = $target->site;

			// TODO-20101213: maybe the 'site' hook should return current site when siteid is 0

			if (!$site)
			{
				$site = $core->site;
			}

			if (!$site)
			{
				return '#missing-associated-site';
			}

			$page_id = $site->metas[$key];

			if ($page_id)
			{
				$page = self::$pages_model[$page_id];

				self::$url_cache_by_siteid[$siteid][$key] = $page ? $page->translation->url_pattern : false;
			}
			else
			{
				self::$url_cache_by_siteid[$siteid][$key] = false;
			}
		}

		$pattern = self::$url_cache_by_siteid[$siteid][$key];

		if (!$pattern)
		{
			return '#uknown-target-for:' . $constructor . '/' . $type;
		}

		return WdRoute::format($pattern, $target);
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
		//TODO-20101213: use ar's site's url

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