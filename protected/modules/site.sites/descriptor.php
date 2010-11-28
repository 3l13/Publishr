<?php

return array
(
	WdModule::T_TITLE => 'Sites',
	WdModule::T_CATEGORY => 'structure',
	WdModule::T_REQUIRED => true,
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'siteid' => 'serial',
					'subdomain' => 'varchar',
					'domain' => 'varchar',
					'path' => 'varchar',
					'tld' => array('varchar', 16),
					'title' => array('varchar', 80),
					'pattern' => 'varchar',
					'model' => array('varchar', 32),
					'language' => array('varchar', 8),
					'sourceid' => 'foreign',
					'is_active' => 'boolean'
				)
			)
		)
	)
);