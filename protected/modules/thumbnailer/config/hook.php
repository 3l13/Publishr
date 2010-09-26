<?php

return array
(
	'objects.methods' => array
	(
		'thumbnail' => array
		(
			array('thumbnailer_WdHooks', 'thumbnail'),

			'instancesof' => 'resources_images_WdActiveRecord'
		),

		'__get_thumbnail' => array
		(
			array('thumbnailer_WdHooks', 'get_thumbnail'),

			'instancesof' => 'resources_images_WdActiveRecord'
		),

		'__get_thumbnail_url' => array
		(
			array('thumbnailer_WdHooks', 'get_thumbnail_url'),

			'instancesof' => 'resources_images_WdActiveRecord'
		)
	)
);