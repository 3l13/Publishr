<?php

return array
(
	'events' => array
	(
		'alter.block.config' => array
		(
			array('thumbnailer_WdHooks', 'alter_block_config')
		),

		'alter.block.manage' => array
		(
			array('thumbnailer_WdHooks', 'alter_block_manage'),

			'instanceof' => 'system_cache_WdModule'
		),

		'properties:before' => array
		(
			array('thumbnailer_WdHooks', 'event_before_config_properties'),

			'instanceof' => 'config_WdOperation'
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
		),

		'stat_thumbnails' => array
		(
			array('thumbnailer_WdHooks', 'stat_cache'),

			'instanceof' => 'system_cache__stat_WdOperation'
		),

		'clear_thumbnails' => array
		(
			array('thumbnailer_WdHooks', 'clear_cache'),

			'instanceof' => 'system_cache__clear_WdOperation'
		)
	)
);