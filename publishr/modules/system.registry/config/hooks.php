<?php

return array
(
	'events' => array
	(
		'alter.block.edit' => array
		(
			array('system_registry_WdHooks', 'alter_block_edit'),

			'instanceof' => array('system_nodes_WdModule', 'user_users_WdModule', 'site_sites_WdModule')
		),

		'operation.save' => array
		(
			array('system_registry_WdHooks', 'operation_save'),

			'instanceof' => array('system_nodes_WdModule', 'user_users_WdModule', 'site_sites_WdModule')
		),

		'operation.delete' => array
		(
			array('system_registry_WdHooks', 'operation_delete'),

			'instanceof' => array('system_nodes_WdModule', 'user_users_WdModule', 'site_sites_WdModule')
		)
	),

	'objects.methods' => array
	(
		'__get_metas' => array
		(
			array('system_registry_WdHooks', '__get_metas'),

			'instanceof' => array('system_nodes_WdActiveRecord', 'user_users_WdActiveRecord', 'site_sites_WdActiveRecord')
		),

		'__get_registry' => array
		(
			array('system_registry_WdHooks', '__get_registry'),

			'instanceof' => 'WdCore'
		)
	)
);