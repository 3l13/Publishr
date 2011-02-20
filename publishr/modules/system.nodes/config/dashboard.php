<?php

return array
(
	'system-nodes-now' => array
	(
		'title' => "From a glance",
		'callback' => array('system_nodes_WdModule', 'dashboard_now'),
		'column' => 0
	),

	'system-nodes-user-modified' => array
	(
		'title' => "Your last modifications",
		'callback' => array('system_nodes_WdModule', 'dashboard_user_modified'),
		'column' => 0
	)
);