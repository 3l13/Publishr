<?php

return array
(
	WdModule::T_TITLE => 'Diaporamas',
	WdModule::T_CATEGORY => 'organize',
	WdModel::T_EXTENDS => 'organize.lists',

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'organize.lists',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'posterid' => 'foreign'
				)
			)
		)
	)
);