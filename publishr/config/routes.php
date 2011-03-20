<?php

return array
(
	'/api/widgets/:class' => array
	(
		'class' => 'widget_get_WdOperation'
	),

	'/api/widgets/:class/:mode' => array
	(
		'class' => 'widget_get_WdOperation'
	),

	'/api/:module/:key/lock' => array
	(
		'class' => 'lock_WdOperation'
	),

	'/api/:module/:key/unlock' => array
	(
		'class' => 'unlock_WdOperation'
	),

	'/api/:module/blocks/:name' => array
	(
		'class' => 'blocks_WdOperation'
	),

	'/api/query-operation/:module/:operation' => array
	(
		'callback' => array('publisher_WdHooks', 'query_operation')
	)
);