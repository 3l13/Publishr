<?php

return array
(
	'patron.markups' => array
	(
		'articles' => array
		(
			array('contents_articles_WdMarkups', 'articles'), array
			(
				'by' => 'date',
				'section' => null,
				'order' => 'desc',
				'limit' => null,
				'date' => null,
				'page' => null,
				'category' => null,
				'tag' => null,
				'author' => null
			)
		),

		'articles:read' => array
		(
			array('contents_articles_WdMarkups', 'articles_read'), array
			(
				'section' => null,
				'order' => 'desc',
				'limit' => 0
			)
		),

		'articles:commented' => array
		(
			array('contents_articles_WdMarkups', 'articles_commented'), array
			(
				'section' => null,
				'order' => 'desc',
				'limit' => 0
			)
		),

		'articles:authors' => array
		(
			array('contents_articles_WdMarkups', 'articles_authors'), array
			(
				'section' => null,
				'order' => 'asc'
			)
		),

		'article' => array
		(
			array('contents_articles_WdMarkups', 'article'), array
			(
				'select' => array('expression' => true, 'mandatory' => true),
				'relative' => null
			)
		),

		'articles:by:date' => array
		(
			array('contents_articles_WdMarkups', 'by_date'), array
			(
				'group' => null,
				'order' => 'asc',
				'start' => 0,
				'limit' => 0
			)
		),

		'articles:by:author' => array
		(
			array('contents_articles_WdMarkups', 'by_author')
		)
	)
);