<?php

return array
(
	array
	(
		'manage' => array
		(

		),

		'create' => array
		(

		),

		'edit' => array
		(

		),

		'/do/components/adjustnodeslist/add/<nid:\d+>' => array
		(
			'callback' => array('WdAdjustNodesList', 'operation_add')
		)
	),

	'defaults' => array
	(
		'module' => 'organize.lists',
		'workspace' => 'taxonomy'
	)
);