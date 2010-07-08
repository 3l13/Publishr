<?php

return array
(
	'alter.block.config' => array('m:resources.files.attached', 'event_alter_block_config'),
	'alter.block.edit' => array('m:resources.files.attached', 'event_alter_block_edit'),
	'operation.save' => array('m:resources.files.attached', 'event_operation_save'),
	'operation.delete' => array('m:resources.files.attached', 'event_operation_delete'),
	'operation.config:before' => array('m:resources.files.attached', 'event_operation_config_before'),
	'ar.property' => array('m:resources.files.attached', 'event_ar_property')
);