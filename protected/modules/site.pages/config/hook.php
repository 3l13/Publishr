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
				'title' => array('mandatory' => true)
			)
		),

		'page:translations' => array
		(
			array('site_pages_WdMarkups', 'translations'), array
			(
				'select' => null
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

		'breadcrumb' => array
		(
			array('site_pages_WdMarkups', 'breadcrumb'), array
			(
				'page' => array('mandatory' => true, 'evaluate' => true)
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
		)
	)
);