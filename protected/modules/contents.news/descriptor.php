<?php

return array
(
	WdModule::T_TITLE => 'Actualités',
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