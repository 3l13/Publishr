<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPublisher extends WdPatron
{
	const VERSION = '2.0.5 (2010-06-20)';

	static public function getSingleton($class='WdPublisher')
	{
		return parent::getSingleton($class);
	}

	public function run()
	{
		global $core, $app;

		$time_start = microtime(true);

		$event = WdEvent::fire
		(
			'publisher.publish:before', array
			(
				'uri' => $_SERVER['REQUEST_URI'],
				'constructor' => array($this, 'run_callback'),
				'constructor_data' => array(),

				'rc' => null
			)
		);

		$html = (!$event || $event->rc === null) ? $this->run_callback() : $event->rc;

//		wd_log('session: \1', array($_SESSION));

		$time_end = microtime(true);
		$time = $time_end - $time_start;

		#
		# log
		#

		$log = null;

		$log_done = WdDebug::fetchMessages('done');
		$log_error = WdDebug::fetchMessages('error');
		$log_debug = WdDebug::fetchMessages('debug');

		if ($app->user_id == 1)
		{
			$messages = array_merge($log_done, $log_error, $log_debug);

			if ($messages/* && WdDebug::$config['verbose']*/)
			{
				$log .= '<ul>';

				foreach ($messages as $message)
				{
					$log .= '<li>' . $message . '</li>' . PHP_EOL;
				}

				$log .= '</ul>' . PHP_EOL;
			}

			if ($log)
			{
				$log = '<div class="wdp-debug"><h6>wdpublisher: debug</h6>' . $log . '</div>';
			}
		}

		$html = str_replace('<!-- $log -->', $log, $html);

		#
		# stats
		#

		$queriesCount = 0;
		$queriesStats = array();

		global $stats;

		foreach ($stats['queries'] as $name => $count)
		{
			$queriesCount += $count;
			$queriesStats[] = $name . ': ' . $count;
		}

		$comment = '<!-- ' . t
		(
			'wdpublisher v:version # publishing time: :elapsed sec, memory usage :memory-usage (peak: :memory-peak), queries: :queries-count (:queries-details)', array
			(
				':elapsed' => number_format($time, 3, '\'', ''),
				':memory-usage' => memory_get_usage(),
				':memory-peak' => memory_get_peak_usage(),
				':queries-count' => $queriesCount,
				':queries-details' => implode(', ', $queriesStats),
				':version' => self::VERSION
			)
		)

		. ' -->' . PHP_EOL;

		echo $html . $comment;
	}

	public function run_callback()
	{
		global $core, $page;

		$uri = $_SERVER['REQUEST_URI'];
		$page = $this->getURIHandler($uri, $_SERVER['QUERY_STRING']);

		if (!$page)
		{
			throw new WdHTTPException
			(
				'The requested URL %uri was not found on this server.', array
				(
					'%uri' => $uri
				),

				404
			);
		}
		else if (!$page->is_online)
		{
			global $app;

			#
			# Offline pages are displayed if the user has ownership, we add the `=!=` marker to the
			# title to indicate that the page is offline but displayed as a preview for the user.
			#
			# Otherwise an HTTP 'Authentification' error is returned.
			#

			if (!$app->user->has_ownership($core->getModule('site.pages'), $page))
			{
				throw new WdHTTPException
				(
					'The requested URL %uri requires authentification.', array
					(
						'%uri' => $uri
					),

					401
				);
			}

			$page->title .= ' =!=';
		}

		#
		# create document
		#

		global $app, $document;

		if (empty($document))
		{
			$document = new WdDocument();
		}

		if ($app->user_id)
		{
			$document->css->add('../../wdpatron/public/patron.css');
		}

		#
		#
		#

		// FIXME: because set() doesn't handle global vars ('$') correctly,
		// we have to set '$page' otherwise, a new variable '$page' is created

		$this->context['$page'] = $page;

		if (isset($page->url_variables))
		{
			$_REQUEST += $page->url_variables;
		}

		$_REQUEST += array
		(
			'page' => 0
		);

		$this->context['this'] = $page;

		#
		# render page's body before publishing the template
		#

		$body = (string) $page->body;

		#
		#
		#

		$file = $_SERVER['DOCUMENT_ROOT'] . '/protected/templates/' . $page->template;
		$template = file_get_contents($file, true);

		$html = $this->publish($template, $page, array('file' => $file));

		#
		# late replace
		#

		$markup = '<!-- $document.js -->';
		$pos = strpos($html, $markup);

		if ($pos !== false)
		{
			$html = substr($html, 0, $pos) . $document->js . substr($html, $pos + strlen($markup));
		}

		$markup = '<!-- $document.css -->';
		$pos = strpos($html, $markup);

		if ($pos !== false)
		{
			$html = substr($html, 0, $pos) . $document->css . substr($html, $pos + strlen($markup));
		}

		return $html;
	}

	protected function getURIHandler($request_uri, $query_string=null)
	{
		$url = $request_uri;

		if ($query_string)
		{
			$url = substr($url, 0, - (strlen($query_string) + 1));
		}

		#
		# if the URL is empty and there are multiple languages defined, we redirect the page to the
		# default language (the first defined in $languages)
		#

		if (!$url && count(WdLocale::$languages) > 1)
		{
			header('Location: /' . WdLocale::$native);

			exit;
		}

		#
		#
		#

		global $core;

		$page = $core->models['site.pages']->loadByPath($url);

		if ($page)
		{
			if ($page->location)
			{
				header('Location: ' . $page->location->url);

				exit;
			}

			if (strpos($page->url_pattern, '<') === false && $page->url != $url)
			{
				//wd_log('page url: \1, url: \2', array($page->url, $url));

				header('HTTP/1.0 301 Moved Permanently');
				header('Location: ' . $page->url . ($query_string ? '?' . $query_string : ''));

				exit;
			}

			#
			# locale... not sure this should be here...
			#

			if ($page->language)
			{
				WdLocale::setLanguage($page->language);
			}
		}

		return $page;
	}
}