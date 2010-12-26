<?php

return array
(
	'events' => array
	(
		'operation.delete' => array
		(
			array('m:taxonomy.terms', 'event_system_nodes_delete')
		)
	)
);