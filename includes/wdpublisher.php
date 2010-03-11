<?php

/* ***** BEGIN LICENSE BLOCK *****
 *
 * This file is part of WdPublisher:
 *
 *     * http://www.weirdog.com
 *     * http://www.wdpublisher.com
 *
 * Software License Agreement (New BSD License)
 *
* Copyright (c) 2007-2010, Olivier Laviale
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 *
 *     * Neither the name of Olivier Laviale nor the names of its
 *       contributors may be used to endorse or promote products derived from this
 *       software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * ***** END LICENSE BLOCK ***** */

define('WDPUBLISHER_VERSION', '2.0.2');

#
#
#

class WdPublisher extends WdPatron
{
	static public function getSingleton($class='WdPublisher')
	{
		return parent::getSingleton($class);
	}

	public function run()
	{
		global $core;

		//wd_log_time('publisher.run:start');

		$time_start = microtime(true);

		if ($core->hasModule('site.cache'))
		{
			$cacheModule = $core->getModule('site.cache');

			$html = $cacheModule->getCached($_SERVER['REQUEST_URI'], array($this, 'run_callback'));
		}
		else
		{
			$html = $this->run_callback();
		}

		#
		# stats
		#

		$time_end = microtime(true);
		$time = $time_end - $time_start;

		//wd_log_time('publisher.run:finish');

		$log_done = WdDebug::fetchMessages('done');
		$log_error = WdDebug::fetchMessages('error');
		$log_debug = WdDebug::fetchMessages('debug');

		$messages = array_merge($log_done, $log_error, $log_debug);

		$log = null;

		if ($messages)
		{
			$log .= '<ul>';

			foreach ($messages as $message)
			{
				$log .= '<li>' . $message . '</li>' . PHP_EOL;
			}

			$log .= '</ul>' . PHP_EOL;

			$html = str_replace('<!-- $log -->', $log, $html);
		}

		$queriesCount = 0;
		$queriesStats = array();

		foreach ($_SESSION['stats']['queries'] as $name => $count)
		{
			$queriesCount += $count;
			$queriesStats[] = $name . ': ' . $count;
		}

		$comment = '<!-- ';
		$comment .= t
		(
			'wdpublisher.:version # time: :elapsed sec, memory usage :memory-usage (peak: :memory-peak), queries: :queries-count (:queries-details)', array
			(
				':elapsed' => number_format($time, 3, '\'', ''),
				':memory-usage' => memory_get_usage(),
				':memory-peak' => memory_get_peak_usage(),
				':queries-count' => $queriesCount,
				':queries-details' => implode(', ', $queriesStats),
				':version' => WDPUBLISHER_VERSION
			)
		);

		/*
		if (self::$function_chain_cache_usage)
		{
			asort(self::$function_chain_cache_usage);

			$comment .= t(' evals cache: \1', array(self::$function_chain_cache_usage));
		}
		*/

		if ($log)//if ((int) $_SERVER['REMOTE_ADDR'] == 192)
		{
			$comment .= PHP_EOL . PHP_EOL . strip_tags($log);
		}

		$comment .= ' -->' . PHP_EOL;

		echo $html . $comment;

		exit;
	}

	public function run_callback()
	{
		global $core, $user, $page;

		$uri = $_SERVER['REQUEST_URI'];
		$page = $this->getURIHandler($uri, $_SERVER['QUERY_STRING']);

		if (!$page)
		{
			header('HTTP/1.0 404 Not Found');

			$uri = wd_entities($uri);

			echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL <code>$uri</code> was not found on this server.</p>
</body></html>
EOT;

			exit;
		}
		else if (!$page->is_online && !$user->hasOwnership($core->getModule('site.pages'), $page))
		{
			header('HTTP/1.0 401 Unauthorized');

			$uri = wd_entities($uri);

			echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>401 Unauthorized</title>
</head><body>
<h1>Unauthorized</h1>
<p>The requested URL <code>$uri</code> requires authentification.</p>
</body></html>
EOT;

			exit;
		}

		#
		# Offline pages are displayed if the user has ownership, we add the `=!=` marker to the
		# title to indicate that the page is offline but displayed as a preview for the user.
		#

		if (!$page->is_online)
		{
			$page->title .= ' =!=';
		}

		if ($page->location)
		{
			header('Location: ' . $page->location->url);

			exit;
		}

		/*
		#
		# TODO: page access
		#

		global $user;

		if ($page->is_restricted)// && $user->isGuest())
		{
			header('Location: /' . WdLocale::$language . '/authenticate?followup=' . urlencode($_SERVER['REQUEST_URI']));

			exit;
		}
		*/

		// FIXME: because set() doesn't handle global vars ('$') correctly,
		// we have to set '$page' otherwise, a new variable '$page' is created

		$this->context['$page'] = $page;

		if (isset($page->url_vars))
		{
			$_REQUEST += $page->url_vars;
		}

		$_REQUEST += array
		(
			'page' => 0
		);

		$layout = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/protected/layouts/' . $page->layout . '.html', true);

		return $this->publish($layout, $page);
	}

	protected function getURIHandler($request_uri, $query_string=null)
	{
		$url = $request_uri;

		if ($query_string)
		{
			$url = substr($url, 0, - (strlen($query_string) + 1));
		}

		#
		# we remove the trailing slash to obtain '/url'
		#

		if ($url{strlen($url) - 1} == '/')
		{
			$url = substr($url, 0, -1);
		}

		#
		# if the URL is empty and there are multiple languages defined, we redirect the page to the
		# default language (the first defined in $languages)
		#

		if (!$url && count(WdLocale::$languages) > 1)
		{
			header('Location: /' . WdLocale::$language);

			exit;
		}

		global $core;

		$module = $core->getModule('site.pages');

		$page = $module->find($url);

		return $page;
	}
}