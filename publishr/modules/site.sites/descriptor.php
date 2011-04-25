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
					'path' => array('varchar', 80),
					'tld' => array('varchar', 16),
					'domain' => array('varchar', 80),
					'subdomain' => array('varchar', 80),
					'title' => array('varchar', 80),
					'admin_title' => array('varchar', 80),
					'model' => array('varchar', 32),
					'weight' => array('integer', 'unsigned' => true),
					'language' => array('varchar', 8),
					'nativeid' => 'foreign',
					'timezone' => array('varchar', 32), // widest is "America/Argentina/Buenos_Aires" with 30 characters
					'status' => array('integer', 'tiny')
				)
			)
		)
	)
);