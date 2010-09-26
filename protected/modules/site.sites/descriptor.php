<?php

return array
(
	WdModule::T_TITLE => 'Sites',
	WdModule::T_CATEGORY => 'site',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'siteid' => 'serial',
					'path' => 'varchar',
					'title' => array('varchar', 80),
					'model' => array('varchar', 32),
					'language' => array('varchar', 8),
					'sourceid' => 'foreign',
					'is_active' => 'boolean'
				)
			)
		)
	)
);