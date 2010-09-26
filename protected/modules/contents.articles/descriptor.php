<?php

return array
(
	WdModule::T_TITLE => 'Articles',
	WdModule::T_DESCRIPTION => 'Articles management',
	WdModule::T_CATEGORY => 'contents',

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'contents',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'dummy' => 'boolean'
				)
			)
		)
	)
);