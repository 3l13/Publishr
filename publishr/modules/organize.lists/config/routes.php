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

	'/api/widgets/adjust-nodes-list/add/<nid:\d+>' => array
	(
		'callback' => array('WdAdjustNodesListWidget', 'operation_add')
	)
);