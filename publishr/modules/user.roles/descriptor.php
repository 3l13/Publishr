<?php

return array
(
	WdModule::T_TITLE => 'Roles',
	WdModule::T_DESCRIPTION => 'Role management',
	WdModule::T_CATEGORY => 'users',
	//WdModuleDescriptor::PRIORITY => 101, // FIXME: install priority ?

	WdModule::T_REQUIRED => true,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'rid' => 'serial',
					'role' => array('varchar', 32, 'unique' => true),
					'perms' => 'text'
				)
			)
		)
	)
);