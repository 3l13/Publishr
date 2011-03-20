<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

	/**
	 * Enables page caching.
	 *
	 * @param system_cache__enable_WdOperation $operation
	 */
	static public function enable_cache(system_cache__enable_WdOperation $operation)
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

	/**
	 * Disables page caching.
	 *
	 * @param system_cache__disable_WdOperation $operation
	 */
	static public function disable_cache(system_cache__disable_WdOperation $operation)
	{
		global $core;

		return $core->vars['cache_rendered_pages'] = false;
	}

	/**
	 * Returns usage of the page cache.
	 *
	 * @param system_cache__stat_WdOperation $operation
	 */
	static public function stat_cache(system_cache__stat_WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/pages';

		return $operation->get_files_stat($path);
	}

	/**
	 * Clears the page cache.
	 */
	static public function clear_cache(system_cache__clear_WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/pages';

		return $operation->clear_files($path);
	}

	/**
	 * An operation (save, delete, online, offline) has invalidated the cache, this we have to
	 * reset it.
	 */
	static public function invalidate_cache()
	{
		$cache = new WdFileCache
		(
			array
			(
				WdFileCache::T_REPOSITORY => WdCore::$config['repository.cache'] . '/pages'
			)
		);

		return $cache->clear();
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

		$cache = new WdFileCache
		(
			array
			(
				WdFileCache::T_COMPRESS => false,
				WdFileCache::T_REPOSITORY => WdCore::$config['repository.cache'] . '/pages'
			)
		);

		$event->rc = $cache->load($key, $constructor, $data);
	}
}