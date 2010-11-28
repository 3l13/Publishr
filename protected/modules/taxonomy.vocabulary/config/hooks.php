<?php

return array
(
	'events' => array
	(
		'operation.save' => array
		(
			array('m:taxonomy.vocabulary', 'event_operation_save'),

			'instanceof' => 'system_nodes_WdModule'
		),

		'alter.block.edit' => array
		(
			array('m:taxonomy.vocabulary', 'alter_block_edit'),

			'instanceof' => 'system_nodes_WdModule'
		),

		'ar.late_methods_definitions' => array
		(
			array('m:taxonomy.vocabulary', 'event_late_methods_definitions'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		)
	)
);