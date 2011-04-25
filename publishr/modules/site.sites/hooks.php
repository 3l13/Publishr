<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class site_sites_WdHooks
{
	static private $model;

	static public function find_by_request($request)
	{
		global $core;

		$sites = $core->vars['sites'];

		if ($sites)
		{
			$sites = unserialize($sites);
		}

		if (!$sites)
		{
			try
			{
				$sites = $core->models['site.sites']->all;
			}
			catch (Exception $e)
			{
				return self::get_default_site();
			}
		}

		$request_uri = $request['REQUEST_URI'];

		$sites_by_ids = array();
		$scores_by_siteid = array();

		foreach ($sites as $site)
		{
			$sites_by_ids[$site->siteid] = $site;
		}

		$parts = explode('.', $request['HTTP_HOST']);

		if (count($parts) == 2)
		{
			array_unshift($parts, 'www');
		}

		list($subdomain, $domain, $tld) = $parts;

		if (preg_match('#/index\.(html|php)#', $request_uri))
		{
			$request_uri = '/';
		}

//		var_dump($request_uri, $sites);
//		echo t('subdomain: "\1", domain: "\2", tld: "\3"', array($subdomain, $domain, $tld));

		$match = null;
		$match_score = -1;
		$is_guest = $core->user_id == 0;

		foreach ($sites as $site)
		{
			$score = 0;

			if ($is_guest && $site->status != 1)
			{
				continue;
			}

			if ($site->tld == $tld)
			{
				$score += 1000;
			}

			if ($site->domain == $domain)
			{
				$score += 100;
			}

			if ($site->subdomain == $subdomain || (!$site->subdomain && $subdomain == 'www'))
			{
				$score += 10;
			}

			if (($site->path && preg_match('#^' . $site->path . '/?#', $request_uri)) || (!$site->path && $request_uri == '/'))
			{
				$score += 1;
			}

			if ($score > $match_score)
			{
				$match = $site;
				$match_score = $score;
			}
		}

		return $match ? $match : self::get_default_site();
	}

	static public function __get_site_id($target)
	{
		$site = self::__get_site($target);

		return $site ? $site->siteid : null;
	}

	static public function __get_site($target)
	{
		if ($target instanceof system_nodes_WdActiveRecord)
		{
			global $core;

			if (!$target->siteid)
			{
				return null;
			}

			return $core->site_id == $target->siteid ? $core->site : $core->models['site.sites'][$target->siteid];
		}

		return self::find_by_request($_SERVER);
	}

	static private function get_default_site()
	{
		global $core;

		$site = new site_sites_WdActiveRecord();

		$site->siteid = 0;
		$site->title = 'Undefined';
		$site->admin_title = '';
		$site->subdomain = '';
		$site->domain = '';
		$site->tld = '';
		$site->path = '';
		$site->language = $core->language;

		return $site;
	}
}