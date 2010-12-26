<?php

return array
(
	'events' => array
	(
		'operation.save' => array
		(
			array('m:resources.images', 'event_operation_save')
		),

		'alter.block.edit' => array
		(
			array('resources_images_WdHooks', 'alter_block_edit'),

			'instanceof' => 'system_nodes_WdModule'
		)
	),

	'objects.methods' => array
	(
		'__get_image' => array
		(
			array('m:resources.images', 'ar_get_image'),

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