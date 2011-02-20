<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPublisher extends WdPatron
{
	const VERSION = '0.6.0-dev (2011-01-22)';

	static public function getSingleton($class='WdPublisher')
	{
		return parent::getSingleton($class);
	}

	protected function search_templates()
	{
		global $core;

		if ($this->templates_searched)
		{
			return;
		}

		$templates = $core->site->partial_templates;

		foreach ($templates as $id => $path)
		{
			$this->addTemplate($id, '!f:' . $path);
		}

		$this->templates_searched = true;
	}

	public function run()
	{
		global $core, $wddebug_time_reference;

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
		# stats
		#

		$queries_count = 0;
		$queries_stats = array();

		foreach ($core->connections as $id => $connection)
		{
			$count = $connection->queries_count;
			$queries_count += $count;
			$queries_stats[] = $id . ': ' . $count;
		}

		$comment = '<!-- ' . t
		(
			'publishr v:version (core: :core_version) # rendering time: :elapsed sec (global time: :framework_elapsed), memory usage :memory-usage (peak: :memory-peak), queries: :queries-count (:queries-details)', array
			(
				':core_version' => WdCore::VERSION,
				':elapsed' => number_format($time, 3, '.', ''),
				':framework_elapsed' => number_format($time_end - $wddebug_time_reference, 3, '.', ''),
				':memory-usage' => memory_get_usage(),
				':memory-peak' => memory_get_peak_usage(),
				':queries-count' => $queries_count,
				':queries-details' => $queries_stats ? implode(', ', $queries_stats) : 'none',
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
		$page = $this->find_page_by_uri($uri, $_SERVER['QUERY_STRING']);

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
			#
			# Offline pages are displayed if the user has ownership, otherwise an HTTP exception
			# with code 401 (Authentication) is thrown. We add the "✎" marker to the title of the
			# page to indicate that the page is offline but displayed as a preview for the user.
			#

			if (!$core->user->has_ownership('site.pages', $page))
			{
				throw new WdHTTPException
				(
					'The requested URL %uri requires authentication.', array
					(
						'%uri' => $uri
					),

					401
				);
			}

			$page->title .= ' ✎';
		}

		$document = $core->document;

		if ($core->user_id)
		{
			$document->css->add('../framework/wdpatron/public/patron.css');
		}

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

		$root = $_SERVER['DOCUMENT_ROOT'];
		$file = $core->site->resolve_path('templates/' . $page->template);

		if (!$file)
		{
			throw new WdException('Unable to resolve path for template: %template', array('%template' => $page->template));
		}

		$template = file_get_contents($root . $file, true);

		$html = $this->publish($template, $page, array('file' => $file));

		#
		# editables
		#

		$admin_menu = $this->get_admin_menu();

		if ($admin_menu)
		{
			$html = str_replace('</body>', $admin_menu . '</body>', $html);
		}

		#
		# late replace
		#

		$markup = '<!-- $document.css -->';
		$pos = strpos($html, $markup);

		if ($pos !== false)
		{
			$html = substr($html, 0, $pos) . $document->css . substr($html, $pos + strlen($markup));
		}
		else
		{
			$html = str_replace('</head>', PHP_EOL . PHP_EOL . $document->css . PHP_EOL . '</head>', $html);
		}

		$markup = '<!-- $document.js -->';
		$pos = strpos($html, $markup);

		if ($pos !== false)
		{
			$html = substr($html, 0, $pos) . $document->js . substr($html, $pos + strlen($markup));
		}
		else
		{
			$html = str_replace('</body>', PHP_EOL . PHP_EOL . $document->js . PHP_EOL . '</body>', $html);
		}

		$markup = '<!-- $log -->';
		$pos = strpos($html, $markup);

		if ($pos !== false)
		{
			$html = substr($html, 0, $pos) . $this->get_log() . substr($html, $pos + strlen($markup));
		}

		WdEvent::fire
		(
			'publisher.publish', array
			(
				'publisher' => $this,
				'uri' => $_SERVER['REQUEST_URI'],
				'rc' => &$html
			)
		);

		return $html;
	}

	protected function get_admin_menu()
	{
		global $core, $page;

		if (!$core->user_id)
		{
			return;
		}

		$document = $core->document;
		$document->css->add('../public/css/admin-menu.css');

		$user = $core->user;

		$contents = null;
		$edit_target = isset($page->node) ? $page->node : $page;

		if (!$edit_target)
		{
			#
			# when the page is cached, 'page' is null because it is not loaded, we should load
			# the page ourselves to present the admin menu on cached pages.
			#

			return;
		}

		$contents .= '<ul style="text-align: center;"><li>';

		if ($user->has_permission(WdModule::PERMISSION_MAINTAIN, $edit_target->constructor))
		{
			$contents .= '<a href="/admin/' . $edit_target->constructor . '/' . $edit_target->nid . '/edit" title="' . t('Edit: !title', array('!title' => $edit_target->title)) . '">' . t('Edit') . '</a> &ndash; ';
		}

		$contents .= '<a href="/api/user.users/disconnect?location=' . wd_entities($_SERVER['REQUEST_URI']) . '">' . t('Disconnect') . '</a> &ndash;
		<a href="/admin/">' . t('Admin') . '</a></li>';
		$contents .= '</ul>';

		#
		#
		#

		$editables_by_category = array();
		$descriptors = $core->modules->descriptors;

		$nodes = array();

		foreach (self::$nodes as $node)
		{
			if (!$node instanceof system_nodes_WdActiveRecord)
			{
				throw new WdException('Not a node object: \1', array($node));
			}

			$nodes[$node->nid] = $node;
		}

		foreach ($nodes as $node)
		{
			if ($node->nid == $edit_target->nid || !$user->has_permission(WdModule::PERMISSION_MAINTAIN, $node->constructor))
			{
				continue;
			}

			// TODO-20101223: use the 'language' attribute whenever available to translate the
			// categories in the user's language.

			$category = isset($descriptors[$node->constructor][WdModule::T_CATEGORY]) ? $descriptors[$node->constructor][WdModule::T_CATEGORY] : 'contents';
			$category = t($category, array(), array('scope' => array('module_category', 'title'), 'language' => $user->language));

			$editables_by_category[$category][] = $node;
		}

		foreach ($editables_by_category as $category => $nodes)
		{
			$contents .= '<div class="panel-section-title">' . wd_entities($category) . '</div>';
			$contents .= '<ul>';

			foreach ($nodes as $node)
			{
				$contents .= '<li><a href="/admin/' . $node->constructor . '/' . $node->nid . '/edit" title="' . t('Edit: !title', array($node->title)) . '">' . wd_entities(wd_shorten($node->title)) . '</a></li>';
			}

			$contents .= '</ul>';
		}

		$rc = '';

		if ($contents)
		{
			$rc  = '<div id="wdpublisher-admin-menu">';
			$rc .= '<div class="panel-title">Publish<span>r</span></div>';
			$rc .= '<div class="contents">';

			$rc .= $contents;

			$rc .= '</div>';
			$rc .= '</div>';
		}

		return $rc;
	}

	protected function get_log()
	{
		global $core;

		$log_done = WdDebug::fetchMessages('done');
		$log_error = WdDebug::fetchMessages('error');
		$log_debug = WdDebug::fetchMessages('debug');

		if ($core->user_id != 1)
		{
			return;
		}

		$messages = array_merge($log_done, $log_error, $log_debug);

		if (!$messages)
		{
			return;
		}

		$log = '<div class="wdp-debug"><h6>publishr: debug</h6><ul>';

		foreach ($messages as $message)
		{
			$log .= '<li>' . $message . '</li>' . PHP_EOL;
		}

		$log .= '</ul></div>' . PHP_EOL;

		return $log;
	}

	protected function find_page_by_uri($request_uri, $query_string=null)
	{
		global $core;

		$url = $request_uri;

		if ($query_string)
		{
			$url = substr($url, 0, - (strlen($query_string) + 1));
		}

		$page = $core->models['site.pages']->loadByPath($url);

		if ($page)
		{
			if ($page->location)
			{
				header('Location: ' . $page->location->url);

				exit;
			}

			$parsed_url_pattern = WdRoute::parse($page->url_pattern);

			if (!$parsed_url_pattern[1] && $page->url != $url)
			{
				header('HTTP/1.0 301 Moved Permanently');
				header('Location: ' . $page->url . ($query_string ? '?' . $query_string : ''));

				exit;
			}
		}
		else if ($url == '/' && $core->site->path)
		{
			header('Location: ' . $core->site->url);

			exit;
		}

		return $page;
	}

	static protected $nodes = array();

	static public function event_nodes_loaded(WdEvent $event)
	{
		self::$nodes = array_merge(self::$nodes, $event->nodes);
	}
}