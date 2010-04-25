<?php

return array
(
	WdModule::T_TITLE => 'Videos',
	WdModule::T_DESCRIPTION => 'Videos management',

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
					'duration' => array('float', array(10, 2), 'unsigned' => true),
					'posterid' => 'foreign'
				)
			)
		)
	)
);