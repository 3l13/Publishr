<?php

//define('WDDOCUMENT_CACHE_JS', true);
//define('WDDOCUMENT_CACHE_CSS', true);

class WdDocument
{
	public $title;
	public $page_title;

	protected function getHead()
	{
		$rc  = '<head>' . PHP_EOL;
		$rc .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . PHP_EOL;

		$rc .= '<title>' . $this->title . '</title>' . PHP_EOL;

		$rc .= $this->getStyleSheets();
		//$rc .= $this->getJavascripts();

		$rc .= '</head>' . PHP_EOL;

		return $rc;
	}

	protected function getBody()
	{
		return '<body></body>';
	}

	public function __toString()
	{
		$body = $this->getBody();
		$head = $this->getHead();

		$rc  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . PHP_EOL;
		$rc .= '<html xmlns="http://www.w3.org/1999/xhtml">' . PHP_EOL;

		$rc .= $head;
		$rc .= $body;

		$rc .= '</html>';

		return $rc;
	}

	static public function getURLFromPath($path, $relative=null)
	{
		$url = null;

		$root = $_SERVER['DOCUMENT_ROOT'];

		#
		# Is the file relative the to the 'relative' path ?
		#
		# if the relative path is not defined, we obtain it from the backtrace stack
		#

		if (!$relative)
		{
			$trace = debug_backtrace();

			$relative = dirname($trace[0]['file']);
		}

		$try = $relative . DIRECTORY_SEPARATOR . $path;

		if (is_file($try))
		{
			$url = $try;
		}

		#
		# If we didn't found the right path, we try the path relative to the script.
		#

		if (!$url)
		{
			$relative = $root . dirname($_SERVER['SCRIPT_NAME']);
			$try = $relative . DIRECTORY_SEPARATOR . $path;

			if (is_file($try))
			{
				$url = $try;
			}
		}

		#
		# relative to the wdpublisher root
		#

		if (!$url)
		{
			$relative = WDPUBLISHER_ROOT;
			$try = $relative . DIRECTORY_SEPARATOR . $path;

			if (is_file($try))
			{
				$url = $try;
			}
		}

		#
		# found nothing !
		#

		if (!$url)
		{
			WdDebug::trigger('Unable to resolve path %path to an URL', array('%path' => $path));

			return;
		}

		#
		# let's turn this ugly absolute path into a lovely URL
		#

		$url = realpath($url);

		if (DIRECTORY_SEPARATOR == '\\')
		{
			$url = str_replace('\\', '/', $url);
		}

		$url = substr($url, strlen($root));

		if ($url{0} != '/')
		{
			$url = '/' . $url;
		}

		return $url;
	}

	/*
	**

	STYLESHEETS

	[name] => [priority]

	**
	*/

	protected $stylesheets = array();

	public function addStyleSheet($url, $priority=0, $root=null)
	{
		if (!$root)
		{
			$trace = debug_backtrace();

			$root = dirname($trace[0]['file']);
		}

//		wd_log('add css <em>\1</em> in <em>\2</em>', $url, $root);

		if (!is_numeric($priority))
		{
			throw new WdException('"priority" must be an integer: \1', array((string) $priority));
		}

		$url = $this->getURLFromPath($url, $root);

		$this->stylesheets[$url] = $priority;
	}

	static function prisort($array)
	{
		$by_priority = array();

		foreach ($array as $key => $value)
		{
			$by_priority[$value][] = $key;
		}

		ksort($by_priority);

		$by_priority = array_reverse($by_priority, true);

		$array = array();

		foreach ($by_priority as $entries)
		{
			$array = array_merge($array, $entries);
		}

		return $array;
	}

	public function getStyleSheetsArray()
	{
		if (empty($this->stylesheets))
		{
			return array();
		}

		return self::prisort($this->stylesheets);
	}

	public function getAssets()
	{
		return array
		(
			'css' => $this->getStyleSheetsArray(),
			'js' => $this->getJavascriptsArray()
		);
	}

	public function getStyleSheets()
	{
		if (empty($this->stylesheets))
		{
			return;
		}

		self::prisort($this->stylesheets);

		$array = self::prisort($this->stylesheets);

		if (defined('WDDOCUMENT_CACHE_CSS'))
		{
			$key = implode(',', $array);

			$cache = new WdFileCache
			(
				array
				(
					//WdFileCache::T_COMPRESS => true,
					WdFileCache::T_REPOSITORY => WdCore::getConfig('repository.cache') . '/document'
				)
			);

			$key = md5($key) . '.css';

			//unlink($_SERVER['DOCUMENT_ROOT'] . $cache->repository . '/' . $key);

			$cache->load($key, array($this, __FUNCTION__ . '_construct'));

			return '<link rel="stylesheet" type="text/css" href="' . $cache->repository . '/' . $key . '" />' . PHP_EOL;
		}
		else
		{
			$rc = '';

			foreach ($array as $url)
			{
				$rc .= '<link rel="stylesheet" type="text/css" href="' . $url . '" />' . PHP_EOL;
			}

			return $rc;
		}
	}

	public function getStyleSheets_construct()
	{
		if (empty($this->stylesheets))
		{
			return;
		}

		self::prisort($this->stylesheets);

		$array = self::prisort($this->stylesheets);

		$rc = '';

		foreach ($array as $url)
		{
			$contents = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $url);

			$contents = preg_replace('/url\(([^\)]+)/', 'url(' . dirname($url) . '/$1', $contents);

			//preg_match_all('/url\(([^\)]+)/', $contents, $matches);

			//echo t('matches for \1 \2', array($url, $matches));

			$rc .= $contents;
		}

		return $rc;
	}

	/*
	**

	JAVASCRIPT

	**
	*/

	protected $javascripts = array();

	public function addJavascript($url, $priority=0, $root=null)
	{
		if (!$root)
		{
			$trace = debug_backtrace();

			$root = dirname($trace[0]['file']);
		}

		if (!is_numeric($priority))
		{
			throw new WdException('"priority" must be an integer : "\1"', $priority);
		}

		$this->javascripts[$this->getURLFromPath($url, $root)] = $priority;
	}

	public function getJavascriptsArray()
	{
		if (empty($this->javascripts))
		{
			return array();
		}

		return self::prisort($this->javascripts);
	}

	public function getJavascripts()
	{
		if (empty($this->javascripts))
		{
			return;
		}

		$array = self::prisort($this->javascripts);

		if (defined('WDDOCUMENT_CACHE_JS'))
		{
			$key = implode(',', $array);

			$cache = new WdFileCache
			(
				array
				(
					//WdFileCache::T_COMPRESS => true,
					WdFileCache::T_REPOSITORY => WdCore::getConfig('repository.cache') . '/document'
				)
			);

			$key = md5($key) . '.js';

			$cache->load($key, array($this, __FUNCTION__ . '_construct'));

			return '<script type="text/javascript" src="' . $cache->repository . '/' . $key . '"></script>' . PHP_EOL;
		}
		else
		{
			$rc = PHP_EOL;

	//		$rc .= l('<!-- \1 -->', $this->javascripts);

			foreach ($array as $url)
			{
				$rc .= '<script type="text/javascript" src="' . $url . '"></script>' . PHP_EOL;
			}

			return $rc;
		}
	}

	public function getJavascripts_construct()
	{
		if (empty($this->javascripts))
		{
			return;
		}

		self::prisort($this->javascripts);

		$array = self::prisort($this->javascripts);

		$rc = '';

		foreach ($array as $url)
		{
			$rc .= file_get_contents($_SERVER['DOCUMENT_ROOT'] . $url);
		}

		return $rc;
	}

}

class WdPDocument extends WdDocument
{
	public $on_setup = false;

	public function __construct()
	{
		$this->addJavascript('../../../wdcore/wdcore.js', 195);
	}

	protected function getBody()
	{
		$contents = $this->getBlock('contents');
		$contents_header = $this->getBlock('contents-header');
		$main = $this->getMain();










		$rc = '<body>';

		$rc .= '<div id="body-wrapper">';

		$rc .= '<div id="quick">';
		$rc .= '<span style="float: left">WdPublisher</span>';
		$rc .= '<span style="float: right">';

		global $user;

		if (!$user->isGuest())
		{
			$rc .= 'Bonjour <a href="' . WdRoute::encode('/profile') . '">' . $user->name . '</a>';

			$rc .= ' <span class="small">(' . ($user->isAdmin() ? 'Admin' : $user->role->role) . ')</span>';

			$rc .= ' <span class="separator">|</span> <a href="' . WdOperation::encode
			(
				'user.users', 'disconnect', array(), true
			)
			. '">Déconnexion</a>';
		}

		$rc .= '</span>';
		$rc .= '<div class="clear"></div></div>';

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

		if ($messages)
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

		$rc .= $this->getJavascripts();

		$rc .= '</body>';

		#
		#
		#

		return $rc;
	}

	protected function getNavigation()
	{
		global $user;

		$rc = '<div id="navigation">';

		if ($user->isGuest())
		{
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

				if (!$user->hasPermission(PERMISSION_ACCESS, $route['module']))
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