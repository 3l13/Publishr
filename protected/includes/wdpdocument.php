<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

//define('WDDOCUMENT_CACHE_JS', true);
//define('WDDOCUMENT_CACHE_CSS', true);

class WdPDocument extends WdDocument
{
	public $on_setup = false;

	public function __construct()
	{
		parent::__construct();

		$this->js->add('../../../wdcore/wdcore.js', -195);
	}

	protected function getHead()
	{
		global $registry;

		$title = $registry->get('site.title', 'WdPublisher');

		if ($title != $this->title)
		{
			$this->title .= ' | ' . $title;
		}

		return parent::getHead();
	}

	protected function getBody()
	{
		$contents = $this->getBlock('contents');
		$contents_header = $this->getBlock('contents-header');
		$main = $this->getMain();






		global $registry;

		$title = $registry->get('site.title', 'WdPublisher');

		/*
		if ($title != 'WdPublisher')
		{
			$this->title  = $title . ' | WdPublisher';
		}
		*/

		$rc = '<body>';

		$rc .= '<div id="body-wrapper">';

		$rc .= '<div id="quick">';
		$rc .= '<span style="float: left">' . $title . '</span>';
		$rc .= '<span style="float: right">';

		global $app;

		$user = $app->user;

		if (!$user->isGuest())
		{
			$rc .= 'Bonjour <a href="' . WdRoute::encode('/profile') . '">' . $user->name . '</a>';

			$rc .= ' <span class="small">(' . ($user->isAdmin() ? 'Admin' : $user->role->role) . ')</span>';

			$rc .= ' <span class="separator">|</span> <a href="' . WdOperation::encode
			(
				'user.users', 'disconnect', array(), true
			)
			. '">Déconnexion</a>';

			$rc .= ' | <a href="/">Voir le site</a>';
		}

		$rc .= '</span>';

		$rc .= '<div class="clear"></div>';
		$rc .= '</div>';

		$rc .= $this->getNavigation();
		//$rc .= $this->getSideMenu();

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

		//$rc .= '<div class="clear"></div>';

		$rc .= '</div>';

		$messages = WdDebug::fetchMessages('debug');

		if ($messages && $user->isAdmin())
		{
			$rc .= '<ul class="wddebug debug">';

			foreach ($messages as $message)
			{
				$rc .= '<li>' . $message . '</li>' . PHP_EOL;
			}

			$rc .= '</ul>';
		}

		//$rc .= '<br style="padding-bottom: 3em" />';
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

	protected function getNavigation()
	{
		global $app;

		$rc = '<div id="navigation">';

		if ($app->user->isGuest())
		{
			$this->title = 'Autentification';

			$rc .= '<ul><li><a href="#">Autentification</a></li></ul>';
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

				if (!$core->hasModule($route['module']))
				{
					continue;
				}

				if (!$app->user->hasPermission(PERMISSION_ACCESS, $route['module']))
				{
					continue;
				}

				$ws = $route['workspace'];

				$links[$ws] = t('@workspaces.' . $ws . '.title');
			}

			asort($links); // TODO: priority, title ?

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

					// TODO: use workspace descriptor to obtain the real name

					$this->page_title = $label;
				}
				else
				{
					$rc .= '<li>';
				}

				$rc .= '<a href="' . WdRoute::encode('/' . $path) . '">' . $label . '</a></li>';
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
		$link = '<a href="http://www.wdpublisher.com/">WdPublisher</a>';

		$rc  = '<div id="footer">';
		$rc .= '<p>';
		$rc .= t($phrase, array(':link' => $link));
		//$rc .= ' › <a href="http://www.wdpublisher.com/docs/">Documentation</a> | <a href="http://www.wdpublisher.com/feedback/">Feedback</a>';
		$rc .= '</p>';
		$rc .= '<p class="version">v0.4.0</p>';
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