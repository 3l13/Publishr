<?php

return array
(
	WdModule::T_TITLE => 'Images',
	WdModule::T_DESCRIPTION => 'Images management',
	WdModule::T_CATEGORY => 'resources',
	WdModule::T_MANDATORY => true,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'resources.files',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'width' => array('integer', 'unsigned' => true),
					'height' => array('integer', 'unsigned' => true),
					'alt' => array('varchar', 80)
				)
			)
		)
	)
);