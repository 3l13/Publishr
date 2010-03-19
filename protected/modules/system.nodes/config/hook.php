<?php

return array
(
	'patron.markups' => array
	(
		array
		(
			'node' => array
			(
				array('system_nodes_WdMarkups', 'node'), array
				(
					'select' => array('mandatory' => true)
				)
			),

			'nodes' => array
			(
				array('system_nodes_WdMarkups', 'nodes'), array
				(
					'scope' => 'system.nodes',
					'order' => 'title',
					'page' => 0,
					'limit' => 10
				)
			)
		)
	)
);