<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WdPublisher extends WdPatron
{
	const VERSION = '0.7.0-dev (2011-04-25)';

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

		$profiling = null;

		foreach ($core->connections as $id => $connection)
		{
			$count = $connection->queries_count;
			$queries_count += $count;
			$queries_stats[] = $id . ': ' . $count;

			if ($core->user_id == 1)
			{
				foreach ($connection->profiling as $note)
				{
					$profiling .= number_format($note[0], 6, '.', ' ') . ': ' . $note[1] . PHP_EOL;
				}
			}
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

		. ($profiling ? PHP_EOL . PHP_EOL . $profiling . PHP_EOL : '')

		. ' -->' . PHP_EOL;

		echo $html . $comment;
	}

	public function run_callback()
	{
		global $core, $page;

		$path = $_SERVER['REQUEST_PATH'];

		// FIXME-20110419: this is terrible !

		if ($core->site->status != 1 && $path == '/' && $core->user->is_guest())
		{
			$site = site_sites_WdHooks::find_by_request($_SERVER, $core->user);

			if ($site != $core->site)
			{
				header('Location: ' . $site->url);

				exit;
			}

			throw new WdHTTPException
			(
				'The requested URL %uri requires authentication.', array
				(
					'%uri' => $_SERVER['REQUEST_URI']
				),

				401
			);
		}

		$page = $this->find_page_by_uri($path, $_SERVER['QUERY_STRING']);

		if (!$page)
		{
			throw new WdHTTPException
			(
				'The requested URL %uri was not found on this server.', array
				(
					'%uri' => $_SERVER['REQUEST_URI']
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
						'%uri' => $_SERVER['REQUEST_URI']
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
		# The page body is rendered before the template is parsed.
		#

		try
		{
			$body = $page->body ? $page->body->render() : '';
		}
		catch (WdHTTPException $e)
		{
			$e->alter_header();
			$body = $e->getMessage();
		}

		$root = $_SERVER['DOCUMENT_ROOT'];
		$file = $core->site->resolve_path('templates/' . $page->template);

		if (!$file)
		{
			throw new WdException('Unable to resolve path for template: %template', array('%template' => $page->template));
		}

		$template = file_get_contents($root . $file, true);

		$html = $this->publish($template, $page, array('file' => $file));

		WdEvent::fire
		(
			'publisher.publish', array
			(
				'publisher' => $this,
				'uri' => $_SERVER['REQUEST_URI'],
				'rc' => &$html
			)
		);

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

		return $html;
	}

	protected function find_page_by_uri($path, $query_string=null)
	{
		global $core;

		$page = $core->models['site.pages']->loadByPath($path);

		if ($page)
		{
			if ($page->location)
			{
				header('Location: ' . $page->location->url);

				exit;
			}

			$parsed_url_pattern = WdRoute::parse($page->url_pattern);

			if (!$parsed_url_pattern[1] && $page->url != $path)
			{
				header('HTTP/1.0 301 Moved Permanently');
				header('Location: ' . $page->url . ($query_string ? '?' . $query_string : ''));

				exit;
			}
		}
		else if ($path == '/' && $core->site->path)
		{
			header('Location: ' . $core->site->url . ($query_string ? '?' . $query_string : ''));

			exit;
		}

		return $page;
	}

	protected function get_admin_menu()
	{
		global $core, $page;

		if (!$core->user_id || $core->user instanceof user_members_WdActiveRecord)
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

		$translator = new WdTranslatorProxi();

		if ($user->language)
		{
			$translator->language = $user->language;
		}

		$contents .= '<ul style="text-align: center;"><li>';

		if ($user->has_permission(WdModule::PERMISSION_MAINTAIN, $edit_target->constructor))
		{
			$contents .= '<a href="' . $core->site->path . '/admin/' . $edit_target->constructor . '/' . $edit_target->nid . '/edit' . '" title="' . $translator->__invoke('Edit: !title', array('!title' => $edit_target->title)) . '">' . $translator->__invoke('Edit') . '</a> &ndash; ';
		}

		$contents .= '<a href="' . wd_entities(WdOperation::encode('user.users/disconnect', array('location'  => $_SERVER['REQUEST_URI']))) . '">' . $translator->__invoke('Disconnect') . '</a> &ndash;
		<a href="' . $core->site->path . '/admin/">' . $translator->__invoke('Admin') . '</a></li>';
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

		$translator->scope = 'module_category.title';

		foreach ($nodes as $node)
		{
			if ($node->nid == $edit_target->nid || !$user->has_permission(WdModule::PERMISSION_MAINTAIN, $node->constructor))
			{
				continue;
			}

			// TODO-20101223: use the 'language' attribute whenever available to translate the
			// categories in the user's language.

			$category = isset($descriptors[$node->constructor][WdModule::T_CATEGORY]) ? $descriptors[$node->constructor][WdModule::T_CATEGORY] : 'contents';
			$category = $translator->__invoke($category);

			$editables_by_category[$category][] = $node;
		}

		$translator->scope = null;

		foreach ($editables_by_category as $category => $nodes)
		{
			$contents .= '<div class="panel-section-title">' . wd_entities($category) . '</div>';
			$contents .= '<ul>';

			foreach ($nodes as $node)
			{
				$contents .= '<li><a href="' . $core->site->path . '/admin/' . $node->constructor . '/' . $node->nid . '/edit' . '" title="' . $translator->__invoke('Edit: !title', array('!title' => $node->title)) . '">' . wd_entities(wd_shorten($node->title)) . '</a></li>';
			}

			$contents .= '</ul>';
		}

		$rc = '';

		if ($contents)
		{
			$rc  = <<<EOT
<div id="wdpublisher-admin-menu">
<div class="panel-title">Publish<span>r</span></div>
<div class="contents">$contents</div>
</div>
EOT;
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

	static protected $nodes = array();

	static public function event_nodes_loaded(WdEvent $event)
	{
		self::$nodes = array_merge(self::$nodes, $event->nodes);
	}
}