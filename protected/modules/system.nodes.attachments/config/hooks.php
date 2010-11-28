<?php

return array
(
	'events' => array
	(
		'alter.block.edit' => array
		(
			array('m:system.nodes.attachments', 'event_alter_block_edit')
		),

		'operation.save' => array
		(
			array('system_nodes_attachments_WdHooks', 'operation_save')
		),

		'ar.property' => array
		(
			array('m:system.nodes.attachments', 'event_ar_property')
		)
	)
);