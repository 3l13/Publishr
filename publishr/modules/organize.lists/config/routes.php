<?php

return array
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

	'/api/components/adjustnodeslist/add/<nid:\d+>' => array
	(
		'callback' => array('WdAdjustNodesList', 'operation_add')
	)
);