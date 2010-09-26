<?php

return array
(
	'objects.methods' => array
	(
		'url' => array
		(
			array('site_pages_view_WdHooks', 'url'),

			'instancesof' => 'system_nodes_WdActiveRecord'
		),

		'__get_url' => array
		(
			array('site_pages_view_WdHooks', 'get_url'),

			'instancesof' => 'system_nodes_WdActiveRecord'
		),

		'absolute_url' => array
		(
			array('site_pages_view_WdHooks', 'absolute_url'),

			'instancesof' => 'system_nodes_WdActiveRecord'
		),

		'__get_absolute_url' => array
		(
			array('site_pages_view_WdHooks', 'get_absolute_url'),

			'instancesof' => 'system_nodes_WdActiveRecord'
		)
	),

	'patron.markups' => array
	(
		'page:contents' => array
		(
			array('site_pages_WdMarkups', 'contents'), array
			(
				'id' => array('mandatory' => true),
				'title' => array('mandatory' => true),
				'editor' => null,
				'render' => array('mandatory' => true, 'default' => 'auto')
			)
		),

		'page:translations' => array
		(
			array('site_pages_WdMarkups', 'translations'), array
			(
				'select' => array('expression' => true, 'mandatory' => true, 'default' => '$page')
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
				'from-level' => null
			)
		),

		'breadcrumb' => array
		(
			array('site_pages_WdMarkups', 'breadcrumb'), array
			(
				'page' => array('expression' => true, 'mandatory' => true, 'default' => 'this')
			)
		),

		'sitemap' => array
		(
			array('site_pages_WdMarkups', 'sitemap'), array
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
				'name' => array('mandatory' => true)
			)
		)
	)
);