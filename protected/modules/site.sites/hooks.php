<?php

class site_sites_WdHooks
{
	static private $model;

	static public function find_by_request($request)
	{
		$filename = $_SERVER['DOCUMENT_ROOT'] . WdCore::$config['repository.cache'] . '/core/sites';

		$sites = null;

		if (is_readable($filename))
		{
			$sites = unserialize(file_get_contents($filename));

//			var_dump($sites);
		}

		if (!$sites)
		{
			global $core;

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

//		echo t('subdomain: "\1", domain: "\2", tld: "\3"', array($subdomain, $domain, $tld));

		foreach ($sites as $site)
		{
			$score = 0;

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

			if ($site->path && preg_match('#^' . $site->path . '/?#', $request_uri))
			{
				$score += 1;
			}

			$scores_by_siteid[$site->siteid] = $score;
		}

		arsort($scores_by_siteid);

		$key = key($scores_by_siteid);
		$site = isset($sites_by_ids[$key]) ? $sites_by_ids[$key] : self::get_default_site();

//		var_dump($site);

		return $site;
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

			return $core->site_id == $target->siteid ? $core->site : $core->models['site.sites'][$target->siteid];
		}

		return self::find_by_request($_SERVER);
	}

	static public function __get_working_site_id()
	{
		global $core;

		// TODO-20101117: NO !! "change_working_site" should not be loaded from POST, there should
		// be a method to change the working site, checking user's permission to do so.
		// THIS IS A SECURITY CONCERN

		if (isset($_POST['change_working_site']))
		{
			$wsid = (int) $_POST['change_working_site'];

			$core->session->application['working_site'] = $wsid;

			header('Location: ' . $_SERVER['REQUEST_URI']);

			exit;
		}
		else if (isset($core->session->application['working_site']))
		{
			$wsid = $core->session->application['working_site'];
		}
		else
		{
//			wd_log('no working site found, use core site: \1', array($core->site));

			$site = $core->site;
			$wsid = $site ? $site->siteid : false;
		}

		return $wsid;
	}

	static public function __get_working_site()
	{
		global $core;

		$site = null;
		$wsid = $core->working_site_id;

		if ($wsid == $core->site_id)
		{
			return $core->site;
		}

		try
		{
			$site = $core->models['site.sites'][$wsid];
		}
		catch (WdException $e) { /* */ }

		if (!$site)
		{
			wd_log_error('unable to load site, create dummy');

			$site = self::get_default_site();
		}

		return $site;
	}

	static private function get_default_site()
	{
		$site = new site_sites_WdActiveRecord();

		$site->siteid = 0;
		$site->title = 'Undefined';
		$site->subdomain = '';
		$site->domain = '';
		$site->tld = '';
		$site->path = '';
		$site->language = WdI18n::$language;

		return $site;
	}
}