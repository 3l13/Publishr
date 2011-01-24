<?php

return array
(
	'/api/components/adjustnode' => array
	(
		'callback' => array('WdAdjustNodeElement', 'operation_get'),
		'controls' => array
		(
			WdModule::CONTROL_AUTHENTICATION => true
		)
	),

	'/api/components/adjustnode/results' => array
	(
		'callback' => array('WdAdjustNodeElement', 'operation_results'),
		'controls' => array
		(
			WdModule::CONTROL_AUTHENTICATION => true
		)
	)
);