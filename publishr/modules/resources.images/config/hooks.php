<?php

return array
(
	'events' => array
	(
		'operation.save' => array
		(
			array('resources_images_WdHooks', 'operation_save'),

			'instanceof' => 'system_nodes_WdModule'
		),

		'operation.config:before' => array
		(
			array('resources_images_WdHooks', 'before_operation_config'),

			'instanceof' => 'contents_WdModule'
		),

		'alter.block.edit' => array
		(
			array('resources_images_WdHooks', 'alter_block_edit'),

			'instanceof' => 'system_nodes_WdModule'
		),

		'alter.block.config' => array
		(
			array('resources_images_WdHooks', 'alter_block_config'),

			'instanceof' => 'contents_WdModule'
		),

		'publisher.publish' => array
		(
			array('resources_images_WdHooks', 'publishr_publish')
		)
	),

	'objects.methods' => array
	(
		'__get_image' => array
		(
			array('resources_images_WdHooks', '__get_image'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		)
	),

	'textmark' => array
	(
		'images.reference' => array
		(
			array('resources_images_WdHooks', 'textmark_images_reference')
		)
	)
);