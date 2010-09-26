<?php

return array
(
	WdModule::T_TITLE => 'Agenda',
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
					'finish' => 'date'
				)
			)
		)
	)
);