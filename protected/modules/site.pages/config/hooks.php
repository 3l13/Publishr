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
		)
	)
);