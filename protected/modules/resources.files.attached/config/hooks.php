<?php

return array
(
	'events' => array
	(
		'alter.block.config' => array
		(
			array('m:resources.files.attached', 'event_alter_block_config')
		),

		'alter.block.edit' => array
		(
			array('m:resources.files.attached', 'event_alter_block_edit')
		),

		'operation.save' => array
		(
			array('m:resources.files.attached', 'event_operation_save')
		),

		'operation.delete' => array
		(
			array('m:resources.files.attached', 'event_operation_delete')
		),

		'operation.config:before' => array
		(
			array('m:resources.files.attached', 'event_operation_config_before')
		)
	),

	'objects.methods' => array
	(
		'__get_attached_files' => array
		(
			array('resources_files_attached_WdHooks', 'get_attached_files'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		)
	)
);