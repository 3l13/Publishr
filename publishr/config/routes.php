<?php

return array
(
	'/api/components/dashboard/order' => array
	(
		'callback' => array('WdPDashboard', 'operation_order')
	),

	'/api/:module/blocks/:name' => array
	(
		'callback' => array('WdPModule', 'route_block')
	)
);