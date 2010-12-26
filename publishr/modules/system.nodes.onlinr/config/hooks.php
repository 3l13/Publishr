<?php

return array
(
	'events' => array
	(
		'alter.block.edit' => array
		(
			array('m:system.nodes.onlinr', 'event_alter_block_edit'),

			'instanceof' => 'system_nodes_WdModule'
		),

		'operation.save' => array
		(
			array('m:system.nodes.onlinr', 'event_operation_save')
		)
	)
);