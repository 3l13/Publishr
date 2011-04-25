<?php

return array
(
	'events' => array
	(
		'resources.files.path.change' => array
		(
			array('site_pages_WdHooks', 'resources_files_path_change')
		),

		'site.pages.url.change' => array
		(
			array('site_pages_WdHooks', 'site_pages_url_change')
		),

		/*
		 * cache support
		 */

		'alter.block.manage' => array
		(
			array('site_pages_WdHooks', 'alter_block_manage'),

			'instanceof' => 'system_cache_WdModule'
		),

		'publisher.publish:before' => array
		(
			array('site_pages_WdHooks', 'before_publisher_publish')
		),

		'operation.save' => array
		(
			array('site_pages_WdHooks', 'invalidate_cache')
		),

		'operation.delete' => array
		(
			array('site_pages_WdHooks', 'invalidate_cache')
		),

		'operation.online' => array
		(
			array('site_pages_WdHooks', 'invalidate_cache')
		),

		'operation.offline' => array
		(
			array('site_pages_WdHooks', 'invalidate_cache')
		)
	),

	'objects.methods' => array
	(
		'url' => array
		(
			array('site_pages_view_WdHooks', 'url'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		),

		'__get_url' => array
		(
			array('site_pages_view_WdHooks', 'get_url'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		),

		'absolute_url' => array
		(
			array('site_pages_view_WdHooks', 'absolute_url'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		),

		'__get_absolute_url' => array
		(
			array('site_pages_view_WdHooks', 'get_absolute_url'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		),

		/*
		 * The following hooks are for the unified cache support
		 */

		'enable_pages' => array
		(
			array('site_pages_WdHooks', 'enable_cache'),

			'instanceof' => 'system_cache__enable_WdOperation'
		),

		'disable_pages' => array
		(
			array('site_pages_WdHooks', 'disable_cache'),

			'instanceof' => 'system_cache__disable_WdOperation'
		),

		'stat_pages' => array
		(
			array('site_pages_WdHooks', 'stat_cache'),

			'instanceof' => 'system_cache__stat_WdOperation'
		),

		'clear_pages' => array
		(
			array('site_pages_WdHooks', 'clear_cache'),

			'instanceof' => 'system_cache__clear_WdOperation'
		),

		/*
		 * views
		 */

		'resolve_view_target' => array
		(
			array('site_pages_view_WdHooks', 'resolve_view_target'),

			'instanceof' => 'site_sites_WdActiveRecord'
		),

		'resolve_view_url' => array
		(
			array('site_pages_view_WdHooks', 'resolve_view_url'),

			'instanceof' => 'site_sites_WdActiveRecord'
		)
	),

	'patron.markups' => array
	(
		'page:content' => array
		(
			array('site_pages_WdMarkups', 'content'), array
			(
				'id' => array('required' => true),
				'title' => array('required' => true),
				'editor' => null,
				'render' => array('required' => true, 'default' => 'auto'),
				'no-wrapper' => false
			)
		),

		'page:translations' => array
		(
			array('site_pages_WdMarkups', 'translations'), array
			(
				'select' => array('expression' => true, 'required' => true, 'default' => '$page')
			)
		),

		'page:languages' => array
		(
			'o:site_pages_languages_WdMarkup', array
			(
			)
		),

		'menu' => array
		(
			array('site_pages_WdMarkups', 'menu'), array
			(
				'select' => null,
				'parent' => null,
				'nest' => true
			)
		),

		'navigation' => array
		(
			'o:site_pages_navigation_WdMarkup', array
			(
				'parent' => 0,
				'depth' => array('default' => 2),
				'min-child' => false,
				'from-level' => null,
				'mode' => null
			)
		),

		'navigation:leaf' => array
		(
			array('site_pages_navigation_WdMarkup', 'navigation_leaf'), array
			(
				'level' => 1,
				'depth' => true,
				'title-link' => true
			)
		),

		'breadcrumb' => array
		(
			array('site_pages_WdMarkups', 'breadcrumb'), array
			(
				'page' => array('expression' => true, 'required' => true, 'default' => 'this')
			)
		),

		'sitemap' => array
		(
//			array('site_pages_WdMarkups', 'sitemap'), array
			'o:site_pages_sitemap_WdMarkup', array
			(
				'parent' => null,
				'nest' => false
			)
		),

		#
		# views
		#

		'call-view' => array
		(
			array('site_pages_WdMarkups', 'call_view'), array
			(
				'name' => array('required' => true)
			)
		),

		#
		# cache
		#

		'cache' => array
		(
			array('site_pages_WdMarkups', 'cache'), array
			(
				'scope' => 'global'
			)
		)
	)
);