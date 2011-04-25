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
		$query_string = $request['QUERY_STRING'];

		if ($query_string)
		{
			$request_uri = substr($request_uri, 0, - (strlen($query_string) + 1));
		}

		if (preg_match('#/index\.(html|php)#', $request_uri))
		{
			$request_uri = '/';
		}

		$parts = array_reverse(explode('.', $request['HTTP_HOST']));

		$tld = null;
		$domain = null;
		$subdomain = null;

		if (isset($parts[0]))
		{
			$tld = $parts[0];
		}

		if (isset($parts[1]))
		{
			$domain = $parts[1];
		}

		if (isset($parts[2]))
		{
			$subdomain = implode('.', array_slice($parts, 2));
		}

		$match = null;
		$match_score = -1;

		foreach ($sites as $site)
		{
			$score = 0;

			if ($site->status != 1 && $core->user->is_guest())
			{
				continue;
			}

			if ($site->tld)
			{
				$score += ($site->tld == $tld) ? 1000 : -1000;
			}

			if ($site->domain)
			{
				$score += ($site->domain == $domain) ? 100 : -100;
			}

			if ($site->subdomain)
			{
				$score += ($site->subdomain == $subdomain || (!$site->subdomain && $subdomain == 'www')) ? 10 : -10;
			}

			$site_path = $site->path;

			if ($site_path)
			{
				$score += ($request_uri == $site_path || preg_match('#^' . $site_path . '/#', $request_uri)) ? 1 : -1;
			}
			else if ($request_uri == '/')
			{
				$score += 1;
			}

			//echo "$site->title ($site->admin_title) scored: $score<br>";

			if ($score > $match_score)
			{
				$match = $site;
				$match_score = $score;
			}
		}

		if (!$match && $request_uri == '/')
		{
			foreach ($sites as $site)
			{
				if ($site->status == 1)
				{
					return $site;
				}
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