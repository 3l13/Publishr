<?php

return array
(
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
			'o:site_pages_navigation_markup', array
			(
				'parent' => 0,
				'depth' => array('default' => 2),
				'min-child' => false
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

		'page:tracker' => array
		(
			array('site_pages_WdMarkups', 'tracker'), array
			(

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