<?php

return array
(
	'alter.block.edit' => array('m:system.nodes.attachments', 'event_alter_block_edit'),
	'operation.save' => array('system_nodes_attachments_WdEvents', 'operation_save'),
	'ar.property' => array('m:system.nodes.attachments', 'event_ar_property'),
);