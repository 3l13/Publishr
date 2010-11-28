<?php

return array
(
	'events' => array
	(
		'operation.delete' => array
		(
			array('m:taxonomy.terms', 'event_system_nodes_delete')
		),

		'ar.property' => array
		(
			array('m:taxonomy.terms', 'event_ar_property'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		)
	)
);