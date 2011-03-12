<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_WdHooks
{
	/**
	 * The callback is called when the `resources.files.path.change` is triggered, allowing us to
	 * update content to the changed path of resource.
	 *
	 * @param WdEvent $event
	 */

	static public function resources_files_path_change(WdEvent $event)
	{
		global $core;

		try
		{
			$model = $core->models['site.pages/contents'];

			$model->execute
			(
				'UPDATE {self} SET content = REPLACE(content, ?, ?)', $event->path
			);
		}
		catch (Exception $e)
		{
			return;
		}
	}

	/**
	 * The callback is called when the `site.pages.url.change` event is triggered, allowing us to
	 * update content to the changed url of the page.
	 *
	 * Note that *only* url within something that looks like a HTML attribute are updated, the
	 * matching pattern is ~="<url>("|/)~
	 *
	 * @param WdEvent $event
	 */
	static public function site_pages_url_change(WdEvent $event)
	{
		global $core;

		try
		{
			$model = $core->models['site.pages/contents'];
		}
		catch (Exception $e)
		{
			return;
		}

		list($old, $new) = $event->path;

		$entries = $model->where('content LIKE ?', '%' . $old . '%')->all;

		foreach ($entries as $entry)
		{
			$content = $entry->content;
			$content = preg_replace('~=\"' . preg_quote($old, '~') . '(\"|\/)~', '="' . $new . '$1', $content);

			if ($content == $entry->content)
			{
				continue;
			}

			$model->execute
			(
				'UPDATE {self} SET content = ? WHERE pageid = ? AND contentid = ?', array
				(
					$content, $entry->pageid, $entry->contentid
				)
			);
		}
	}

	/*
	 * The following hooks are for the unified cache support
	 */

	static public function alter_block_manage(WdEvent $event)
	{
		global $core;

		$event->caches['pages'] = array
		(
			'title' => 'Pages',
			'description' => "Pages rendues par Publishr.",
			'group' => 'contents',
			'state' => !empty($core->vars['cache_rendered_pages']),
			'size_limit' => false,
			'time_limit' => array(7, 'Jours')
		);
	}

	static public function operation_activate_for_pages(system_cache_WdModule $target, WdOperation $operation)
	{
		global $core;

		$root = $_SERVER['DOCUMENT_ROOT'];
		$path = WdCore::$config['repository.cache'] . '/pages';

		if (!is_writable($root . $path))
		{
			wd_log_error("%path is missing or not writable", array('%path' => $path));

			return false;
		}

		return $core->vars['cache_rendered_pages'] = true;
	}

	static public function operation_deactivate_for_pages(system_cache_WdModule $target, WdOperation $operation)
	{
		global $core;

		return $core->vars['cache_rendered_pages'] = false;
	}

	static public function operation_usage_for_pages(system_cache_WdModule $target, WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/pages';

		return $target->get_files_usage($path);
	}

	static public function operation_clear_for_pages(system_cache_WdModule $target, WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/pages';

		return $target->clear_files($path);
	}

	static public function clear_cache()
	{
		global $core;

		$path = WdCore::$config['repository.cache'] . '/pages';

		try
		{
			$core->modules['system.cache']->clear_files($path);
		}
		catch (Exception $e) { /* */ }
	}

	static private function get_pages_cache()
	{
		static $cache;

		if (!$cache)
		{
			$cache = new WdFileCache
			(
				array
				(
					WdFileCache::T_COMPRESS => false,
					WdFileCache::T_REPOSITORY => WdCore::$config['repository.cache'] . '/pages'
				)
			);
		}

		return $cache;
	}

	static public function before_publisher_publish(WdEvent $event)
	{
		global $core;

		if ($_POST || !$core->vars['cache_rendered_pages'])
		{
			return;
		}

		$constructor = $event->constructor;
		$data = $event->constructor_data;

		$key = sprintf('%08x-%08x-%s', $core->site_id, (int) $core->user_id, sha1($event->uri));

		$event->rc = self::get_pages_cache()->load($key, $constructor, $data);
	}
}