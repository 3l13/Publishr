<?php

/**
 * This file is part of the WdElements framework
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdelements/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdelements/license/
 */

class WdDocument extends WdObject
{
	public $title;
	public $page_title;

	public $js;
	public $css;

	static protected function resolve_root()
	{
		$stack = debug_backtrace();

		foreach ($stack as $trace)
		{
			if (empty($trace['file']) || $trace['file'] == __FILE__)
			{
				continue;
			}

			return dirname($trace['file']);
		}
	}

	static public function getURLFromPath($path, $relative=null)
	{
		return self::resolve_url($path, $relative);
	}

	static public function resolve_url($path, $relative=null)
	{
		if (strpos($path, 'http://') === 0)
		{
			return $path;
		}

		$root = $_SERVER['DOCUMENT_ROOT'];

		#
		# Is the file relative the to the 'relative' path ?
		#
		# if the relative path is not defined, we obtain it from the backtrace stack
		#

		if (!$relative)
		{
			$relative = self::resolve_root();
		}

		$tries = array
		(
			'',
			$relative,
			$root . dirname($_SERVER['SCRIPT_NAME']),
			PUBLISHR_ROOT,
			$root
		);

		$url = null;
		$i = 0;

		foreach ($tries as &$try)
		{
			$i++;
			$try .= DIRECTORY_SEPARATOR . $path;

			if (!is_file($try))
			{
				continue;
			}

			$url = $try;

			break;
		}

		#
		# found nothing !
		#

		if (!$url)
		{
			WdDebug::trigger('Unable to resolve path %path to an URL, tried: :tried', array('%path' => $path, ':tried' => implode(', ', array_slice($tries, 0, $i))));

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





	/**
	 *
	 */

	public function __construct()
	{
		$this->js = new WdDocumentJSCollector($this);
		$this->css = new WdDocumentCSSCollector($this);
	}











	protected function getHead()
	{
		$rc  = '<head>' . PHP_EOL;
		$rc .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . PHP_EOL;

		$rc .= '<title>' . $this->title . '</title>' . PHP_EOL;

		$rc .= $this->css;

		$rc .= '</head>' . PHP_EOL;

		return $rc;
	}

	protected function getBody()
	{
		return '<body></body>';
	}

	public function __toString()
	{
		try
		{
			$body = $this->getBody();
			$head = $this->getHead();

			$rc  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . PHP_EOL;
			$rc .= '<html xmlns="http://www.w3.org/1999/xhtml">' . PHP_EOL;

			$rc .= $head;
			$rc .= $body;

			$rc .= '</html>';
		}
		catch (Exception $e)
		{
			$rc = (string) $e;
		}

		return $rc;
	}



	public function getAssets()
	{
		return array
		(
			'css' => $this->css->get(),
			'js' => $this->js->get()
		);
	}
}

class WdDocumentCollector
{
	protected $document;
	protected $collected = array();
	public $use_cache = false;

	public function __construct($document)
	{
		$this->document = $document;

		if (!empty(WdCore::$config['cache assets']))
		{
			$this->use_cache = true;
		}
	}

	public function add($path, $weight=0, $root=null)
	{
		$url = $this->document->getURLFromPath($path, $root);

		$this->collected[$url] = $weight;
	}

	public function get()
	{
		$by_priority = array();

		foreach ($this->collected as $url => $priority)
		{
			$by_priority[$priority][] = $url;
		}

		ksort($by_priority);

		$sorted = array();

		foreach ($by_priority as $urls)
		{
			$sorted = array_merge($sorted, $urls);
		}

		return $sorted;
	}

	public function cache_construct(WdFileCache $cache, $key, array $userdata) { }
}

class WdDocumentCSSCollector extends WdDocumentCollector
{
	public function __toString()
	{
		$collected = $this->get();

		#
		# cached output
		#

		try
		{
			if ($this->use_cache)
			{
				$recent = 0;
				$root = $_SERVER['DOCUMENT_ROOT'];

				foreach ($collected as $file)
				{
					$recent = max($recent, filemtime($root . $file));
				}

				$cache = new WdFileCache
				(
					array
					(
						WdFileCache::T_REPOSITORY => WdCore::$config['repository.cache'] . '/assets',
						WdFileCache::T_MODIFIED_TIME => $recent
					)
				);

				$key = sha1(implode(',', $collected)) . '.css';

				$rc = $cache->get($key, array($this, 'cache_construct'), array($collected));

				if ($rc)
				{
					$list = json_encode($collected);

					return <<<EOT

<link type="text/css" href="{$cache->repository}/{$key}" rel="stylesheet" />

<script type="text/javascript">

var document_cached_css_assets = $list;

</script>

EOT;

				}
			}
		}
		catch (Exception $e) { echo $e; }

		#
		# default ouput
		#

		$rc = '';

		foreach ($collected as $url)
		{
			$rc .= '<link type="text/css" href="' . wd_entities($url) . '" rel="stylesheet" />' . PHP_EOL;
		}

		return $rc;
	}

	public function cache_construct(WdFileCache $cache, $key, array $userdata)
	{
		$args = func_get_args();

		list($collected) = $userdata;

		$rc = '/* Compiled CSS file generated by WdDocument */' . PHP_EOL . PHP_EOL;

		foreach ($collected as $url)
		{
			$contents = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $url);
			$contents = preg_replace('/url\(([^\)]+)/', 'url(' . dirname($url) . '/$1', $contents);

			$rc .= $contents . PHP_EOL;
		}

		file_put_contents(getcwd() . '/' . $key, $rc);

		return $key;
	}
}

class WdDocumentJSCollector extends WdDocumentCollector
{
	public function __toString()
	{
		$collected = $this->get();

		#
		# cached ouput
		#

		try
		{
			if ($this->use_cache)
			{
				$recent = 0;
				$root = $_SERVER['DOCUMENT_ROOT'];

				foreach ($collected as $file)
				{
					$recent = max($recent, filemtime($root . $file));
				}

				$cache = new WdFileCache
				(
					array
					(
						WdFileCache::T_REPOSITORY => WdCore::$config['repository.cache'] . '/assets',
						WdFileCache::T_MODIFIED_TIME => $recent
					)
				);

				$key = sha1(implode(',', $collected)) . '.js';

				$rc = $cache->get($key, array($this, 'cache_construct'), array($collected));

				if ($rc)
				{
					return PHP_EOL . PHP_EOL . '<script type="text/javascript" src="' . $cache->repository . '/' . $key . '"></script>' . PHP_EOL . PHP_EOL;
				}
			}
		}
		catch (Exception $e) { echo $e; }

		#
		# default ouput
		#

		$rc = '';

		foreach ($collected as $url)
		{
			$rc .= '<script type="text/javascript" src="' . wd_entities($url) . '"></script>' . PHP_EOL;
		}

		return $rc;
	}

	public function cache_construct(WdFileCache $cache, $key, array $userdata)
	{
		$args = func_get_args();

		list($collected) = $userdata;

		$rc = '';

		foreach ($collected as $url)
		{
			$rc .= file_get_contents($_SERVER['DOCUMENT_ROOT'] . $url);
		}

		$list = json_encode($collected);

		$rc = <<<EOT
//
// Compiled Javascript file generated by WdDocument
//

var document_cached_js_assets = $list;

// BEGIN

EOT

		. $rc;

		file_put_contents(getcwd() . '/' . $key, $rc);

		return $key;
	}
}