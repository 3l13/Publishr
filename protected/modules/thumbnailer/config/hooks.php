<?php

return array
(
	'events' => array
	(
		'alter.block.config' => array
		(
			array('thumbnailer_WdHooks', 'event_alter_block_config')
		),

		'operation.config:before' => array
		(
			array('thumbnailer_WdHooks', 'event_operation_config_before')
		)
	),

	'objects.methods' => array
	(
		'thumbnail' => array
		(
			array('thumbnailer_WdHooks', 'object_thumbnail'),

			'instanceof' => 'resources_images_WdActiveRecord'
		),

		'__get_thumbnail' => array
		(
			array('thumbnailer_WdHooks', 'object_get_thumbnail'),

			'instanceof' => 'resources_images_WdActiveRecord'
		)
	)
);