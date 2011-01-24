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
		),

		'operation_activate_for_thumbnails' => array
		(
			array('thumbnailer_WdHooks', 'operation_activate_for_thumbnails'),

			'instanceof' => 'system_cache_WdModule'
		),

		'operation_deactivate_for_thumbnails' => array
		(
			array('thumbnailer_WdHooks', 'operation_deactivate_for_thumbnails'),

			'instanceof' => 'system_cache_WdModule'
		),

		'operation_usage_for_thumbnails' => array
		(
			array('thumbnailer_WdHooks', 'operation_usage_for_thumbnails'),

			'instanceof' => 'system_cache_WdModule'
		),

		'operation_clear_for_thumbnails' => array
		(
			array('thumbnailer_WdHooks', 'operation_clear_for_thumbnails'),

			'instanceof' => 'system_cache_WdModule'
		)
	)
);