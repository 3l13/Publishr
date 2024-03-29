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
			array('resources_files_attached_WdHooks', 'event_alter_block_edit'),

			'instanceof' => 'system_nodes_WdModule'
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
		),

		'operation.config' => array
		(
			array('m:resources.files.attached', 'event_operation_config')
		)
	),

	'objects.methods' => array
	(
		'__get_attachments' => array
		(
			array('resources_files_attached_WdHooks', 'get_attachments'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		)
	),

	'patron.markups' => array
	(
		'node:attachments' => array
		(
			array('resources_files_attached_WdHooks', 'markup_node_attachments')
		)
	)
);