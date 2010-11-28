<?php

return array
(
	'events' => array
	(
		'publisher.publish:before' => array
		(
			array('m:site.cache', 'get')
		),

		'operation.save' => array
		(
			array('m:site.cache', 'clear')
		),

		'operation.delete' => array
		(
			array('m:site.cache', 'clear')
		),

		'operation.online' => array
		(
			array('m:site.cache', 'clear')
		),

		'operation.offline' => array
		(
			array('m:site.cache', 'clear')
		),

		'clear page cache' => array
		(
			array('m:site.cache', 'clear')
		)
	),

	'patron.markups' => array
	(
		'cache' => array
		(
			array('site_cache_WdMarkups', 'cache'), array
			(
				'scope' => 'global'
			)
		)
	)
);