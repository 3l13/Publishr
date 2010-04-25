<?php

return array
(
	'patron.markups' => array
	(
		'news:head' => array
		(
			array('contents_news_WdMarkups', 'head'), array
			(
				'select' => array('mandatory' => true, 'evaluate' => true),
				'limit' => 10,
				'page' => 0
			)
		),

		'news' => array
		(
			array('contents_news_WdMarkups', 'news'), array
			(
				'select' => array('mandatory' => true, 'evaluate' => true)
			)
		)
	)
);