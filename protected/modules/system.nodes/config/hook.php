<?php

return array
(
	'patron.markups' => array
	(
		'node' => array
		(
			'o:system_nodes_view_WdMarkup', /*array('system_nodes_WdMarkups', 'node'),*/ array
			(
				'select' => array('expression' => true, 'mandatory' => true)
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
);