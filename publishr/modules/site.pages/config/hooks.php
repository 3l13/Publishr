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
			array('site_pages_WdHooks', 'clear_cache')
		),

		'operation.delete' => array
		(
			array('site_pages_WdHooks', 'clear_cache')
		),

		'operation.online' => array
		(
			array('site_pages_WdHooks', 'clear_cache')
		),

		'operation.offline' => array
		(
			array('site_pages_WdHooks', 'clear_cache')
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

		'operation_activate_for_pages' => array
		(
			array('site_pages_WdHooks', 'operation_activate_for_pages'),

			'instanceof' => 'system_cache_WdModule'
		),

		'operation_deactivate_for_pages' => array
		(
			array('site_pages_WdHooks', 'operation_deactivate_for_pages'),

			'instanceof' => 'system_cache_WdModule'
		),

		'operation_usage_for_pages' => array
		(
			array('site_pages_WdHooks', 'operation_usage_for_pages'),

			'instanceof' => 'system_cache_WdModule'
		),

		'operation_clear_for_pages' => array
		(
			array('site_pages_WdHooks', 'operation_clear_for_pages'),

			'instanceof' => 'system_cache_WdModule'
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
				'no-wrap' => false
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