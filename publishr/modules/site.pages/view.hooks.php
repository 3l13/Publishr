<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

		$siteid = $target->siteid ? $target->siteid : $core->site_id;
		$key = 'views.targets.' . $constructor . '/' . $type;

		if (isset(self::$url_cache_by_siteid[$siteid][$key]))
		{
			$pattern = self::$url_cache_by_siteid[$siteid][$key];
		}
		else
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

			$pattern = false;
			$page_id = $site->metas[$key];

			if ($page_id)
			{
				$pattern = self::$pages_model[$page_id]->url_pattern;
			}

			self::$url_cache_by_siteid[$siteid][$key] = $pattern;
		}

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
		global $core;

		$site = $ar->site ? $ar->site : $core->site;

		return $site->url . substr(self::url($ar, $type), strlen($site->path));
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

	static private $view_target_cache=array();

	/**
	 * Returns the page target of a view.
	 *
	 * @return site_pages_WdActiveRecord The page target for the specified view identifier.
	 */
	static public function resolve_view_target(site_sites_WdActiveRecord $site, $viewid)
	{
		global $core;

		if (isset(self::$view_target_cache[$viewid]))
		{
			return self::$view_target_cache[$viewid];
		}

		$targetid = $site->metas['views.targets.' . strtr($viewid, '.', '_')];

		return self::$view_target_cache[$viewid] = $targetid ? $core->models['site.pages'][$targetid] : false;
	}

	static private $view_url_cache=array();

	/**
	 * Returns the URL of a view.
	 *
	 * @param site_sites_WdActiveRecord $site
	 * @param string $viewid
	 */
	static public function resolve_view_url(site_sites_WdActiveRecord $site, $viewid)
	{
		if (isset(self::$view_url_cache[$viewid]))
		{
			return self::$view_url_cache[$viewid];
		}

		$target = $site->resolve_view_target($viewid);

		return self::$view_url_cache[$viewid] = $target ? $target->url : '#unknown-target-for-view-' . $viewid;
	}
}