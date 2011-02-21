<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPDocument extends WdDocument
{
	public $on_setup = false;

	public function __construct()
	{
		parent::__construct();

		$cache_assets = WdCore::$config['cache assets'];

		$this->css->use_cache = $cache_assets;
		$this->js->use_cache = $cache_assets;
	}

	protected function getHead()
	{
		global $core;

		$site_title = $core->working_site->title;

		$this->title = 'Publishr (' . $site_title . ')';

		//$this->css->add('http://fonts.googleapis.com/css?family=Droid+Sans:regular,bold&subset=latin');
		//$this->css->add('http://fonts.googleapis.com/css?family=Droid+Serif:regular,italic,bold,bolditalic&subset=latin');

		return parent::getHead();
	}

	protected function getBody()
	{
		global $core;

		$contents = $this->getBlock('contents');
		$contents_header = $this->getBlock('contents-header');
		$main = $this->getMain();

		$user = $core->user;

		$rc  = $this->get_block_shortcuts();

		$rc .= $this->getNavigation();

		$rc .= '<div id="contents-wrapper">';
		$rc .= '<h1>' . t($this->page_title) . '</h1>';

		$rc .= '<div id="contents-header">';
		$rc .= $contents_header;
		$rc .= '</div>';

		$rc .= '<div id="contents">';

		#
		# messages
		#

		$messages = WdDebug::fetchMessages('error');

		if ($messages)
		{
			$rc .= '<ul class="wddebug error">';

			foreach ($messages as $message)
			{
				$rc .= '<li>' . $message . '</li>' . PHP_EOL;
			}

			$rc .= '</ul>';
		}

		$messages = WdDebug::fetchMessages('done');

		if ($messages)
		{
			$rc .= '<ul class="wddebug done">';

			foreach ($messages as $message)
			{
				$rc .= '<li>' . $message . '</li>' . PHP_EOL;
			}

			$rc .= '</ul>';
		}

		#
		#
		#

		$rc .= $contents;
		$rc .= $main;

		$messages = WdDebug::fetchMessages('debug');

		if ($messages && $user->is_admin())
		{
			$rc .= '<ul class="wddebug debug">';

			foreach ($messages as $message)
			{
				$rc .= '<li>' . $message . '</li>' . PHP_EOL;
			}

			$rc .= '</ul>';
		}

		$rc .= '</div>';
		$rc .= '</div>';

		$rc .= '</div>';

		$rc .= $this->getFooter();

		$rc .= $this->js;

		$rc .= '</body>';

		#
		#
		#

		return $rc;
	}

	protected function get_block_shortcuts()
	{
		global $core;

		$user = $core->user;
		$site = $core->working_site;

		$site_title = wd_entities($site->admin_title);

		if (!$site_title)
		{
			$site_title = wd_entities($site->title) . '<span class="language">:' . $site->language;
		}

		$sites_list = '<a href="' . $site->url . '">' . $site_title . '</a>';

		if (!$user->is_guest())
		{
			$rc  = '<body class="admin">';
			$rc .= '<div id="body-wrapper">';

			try
			{
				$options = $core->models['site.sites']
				->select('siteid, IF(admin_title != "", admin_title, concat(title, ":", language))')
				->where('siteid != ?', $site->siteid)
				->order('admin_title, title')
				->pairs;

				if ($options)
				{
					$uri = $_SERVER['REQUEST_URI'];
					$sites_list = '<ul><li>' . $sites_list . '</li>';

					foreach ($options as $asiteid => $asite_title)
					{
						$sites_list .= '<li data-siteid="' . $asiteid . '"><a href="/api/site.sites/' . $asiteid . '/as-working-site?continue=' . wd_entities($uri) . '">' . $asite_title . '</a></li>';
					}

					$sites_list .= '</ul>';
				}
			}
			catch (Exception $e) { /**/ }

			$rc .= '<div id="quick">';

//			var_dump($sites_list);

			$rc .= '<div class="sites"><span style="float: left">←&nbsp;</span>' . $sites_list . '</div>';

			$rc .= '<span style="float: right">';

			$roles = '';

			if ($user->is_admin())
			{
				$roles = 'Admin';
			}
			else if ($user->has_permission(WdModule::PERMISSION_ADMINISTER, 'user.roles'))
			{
				foreach ($user->roles as $role)
				{
					$roles .= ', <a href="/admin/user.roles/' . $role->rid . '/edit">' . $role->role . '</a>';
				}

				$roles = substr($roles, 2);
			}
			else
			{
				foreach ($user->roles as $role)
				{
					$roles .= ', ' . $role->role;
				}

				$roles = substr($roles, 2);
			}

			$rc .= t('Hello :username', array(':username' => '<a href="/admin/profile">' . $user->name . '</a>'));
			$rc .= ' <span class="small">(' . $roles . ')</span>';
			$rc .= ' <span class="separator">|</span> <a href="/api/user.users/disconnect">' . t('label.disconnect') . '</a>';
//			$rc .= ' <span class="separator">|</span> <a href="' . $core->working_site->url . '">' . t('See the website') . '</a>';

			$rc .= '</span>';

			$rc .= '<div class="clear"></div>';
			$rc .= '</div>';
		}
		else
		{
			$site = $core->site;

			$this->page_title = 'Publish<span>r</span>';

			$rc  = '<body class="admin page-slug-authenticate">';
			$rc .= '<div id="body-wrapper">';

			$rc .= '<div id="quick">';
			$rc .= '←&nbsp;<a href="' . $site->url . '" class="home">' . t($site->title) . '</a>';
			$rc .= '</div>';
		}


		return $rc;
	}

	protected function getNavigation()
	{
		global $core;

		$user = $core->user;

		$rc = '<div id="navigation">';

		if ($user->is_guest())
		{
			$this->title = 'Publishr';

			return;
			//$rc .= '<ul><li><a href="#">Autentification</a></li></ul>';
		}
		else
		{
			global $routes, $matching_route, $core;

			//wd_log('routes: \1', array($routes));

			$links = array();

			foreach ($routes as $route)
			{
				if (empty($route['index']))
				{
					continue;
				}

				if (empty($route['workspace']))
				{
					continue;
				}

				$module_id = $route['module'];

				if (empty($core->modules[$module_id]))
				{
					continue;
				}

				$permission = isset($route['permission']) ? $route['permission'] : WdModule::PERMISSION_ACCESS;

				if (!$user->has_permission($permission, $module_id))
				{
					continue;
				}

				$ws = $route['workspace'];

				$links[$ws] = t($ws, array(), array('scope' => array('module_category', 'title')));
			}

			uasort($links, 'wd_unaccent_compare_ci');

			$links = array_merge
			(
				array
				(
					'dashboard' => 'Dashboard'
				),

				$links
			);

			$selected = $matching_route ? $matching_route['workspace'] : 'dashboard';

			$rc .= '<ul>';

			foreach ($links as $path => $label)
			{
				if (strpos($selected, $path) === 0)
				{
					$rc .= '<li class="selected">';

					$this->page_title = $label;
				}
				else
				{
					$rc .= '<li>';
				}

				$rc .= '<a href="/admin/' . $path . '">' . $label . '</a></li>';
			}

			$rc .= '</ul>';

			//$rc .= '<form action="" id="search"><input type="text" class="empty" value="Search"/></form>';
		}

		$rc .= '<span id="loader">loading</span>';

		$rc .= '</div>';

		return $rc;
	}

	protected function getMain()
	{
		return;

		$main = $this->getBlock('main');

		$rc = '';
//		$rc .= '<div id="contents">';

		if ($main)
		{
			$rc .= '<div class="group" style="-moz-box-shadow: 0 25px 15px -20px rgba(0, 0, 0, 0.2)">';
			$rc .= $main;
			$rc .= '</div>';
		}

		//$rc .= '</div>';

		$journal = $this->getJournal();

		if ($journal)
		{
			$rc .= '<div class="group" style="margin-top: 3em">';
			$rc .= $journal;
			$rc .= '</div>';
		}

		return $rc;
	}

	protected function getFooter()
	{
		$phrases = array
		(
			'Thank you for creating with :link',
			'Light and sweet edition with :link',
			':link is super green'
		);

		$phrase = $phrases[date('md') % count($phrases)];
		$link = '<a href="http://www.wdpublisher.com/" target="_blank">Publishr</a>';

		$rc  = '<div id="footer" class="-sticky">';
		$rc .= '<p>';
		$rc .= t($phrase, array(':link' => $link));
		$rc .= ' › <a href="http://www.wdpublisher.com/docs/" target="_blank">Documentation</a>';// | <a href="http://www.wdpublisher.com/feedback/">Feedback</a>';
		$rc .= '</p>';
		$rc .= '<p class="version">v' . preg_replace('#\s*\(.*#', '', WdPublisher::VERSION) . '</p>';
		$rc .= '<div class="clear"></div>';
		$rc .= '</div>';

		return $rc;
	}

	public function getJournal()
	{
		$rc = WdDebug::fetchMessages('debug');

		if ($rc)
		{
			return '<div id="journal"><h2>Journal</h2>' . $rc . '</div>';
		}
	}

	/*
	**

	BLOCKS

	**
	*/

	var $blocks = array();

	function addToBlock($contents, $blockname)
	{
		if (!is_string($contents))
		{
			throw new WdException('Wrong type for block contents');
		}

		$this->blocks[$blockname][] = $contents;
	}

	function getBlock($name)
	{
		if (empty($this->blocks[$name]))
		{
			return;
		}

		$rc = '';

		foreach ($this->blocks[$name] as $contents)
		{
			$rc .= $contents;
		}

		return $rc;
	}
}