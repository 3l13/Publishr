<?php

return array
(
	'system-nodes-now' => array
	(
		'title' => "D'un coup d'oeil",
		'callback' => array('system_nodes_WdModule', 'dashboard_now'),
		'column' => 0
	),

	'system-nodes-user-modified' => array
	(
		'title' => "Vos dernières modifications",
		'callback' => array('system_nodes_WdModule', 'dashboard_user_modified'),
		'column' => 0
	)
);