<?php

return array
(
	WdModule::T_TITLE => 'Terms descriptions',
	WdModule::T_DESCRIPTION => 'Add descriptions to terms',
	WdModule::T_PERMISSION => PERMISSION_NONE,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_IMPLEMENTS => array
			(
				array('model' => 'taxonomy.terms/primary')
			),

			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'vtid' => 'primary',
					'description' => 'text'
				)
			)
		)
	)
);