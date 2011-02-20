<?php

return array
(
	'events' => array
	(
		'operation.disconnect:before' => array
		(
			array('publisher_WdHooks', 'before_operation_disconnect'),

			'instanceof' => 'user_users_WdModule'
		),

		'operation.components/*:before' => array
		(
			array('publisher_WdHooks', 'before_operation_components_all')
		),

		'operation.components/*' => array
		(
			array('publisher_WdHooks', 'operation_components_all')
		),

		'publisher.nodes_loaded' => array
		(
			array('WdPublisher', 'event_nodes_loaded')
		)
	)
);