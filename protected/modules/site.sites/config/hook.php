<?php

return array
(
	'objects.methods' => array
	(
		'__get_site' => array
		(
			array('site_sites_WdHooks', 'get_site'),

			'instancesof' => array('WdApplication', 'system_nodes_WdActiveRecord')
		),

		'__get_working_site' => array
		(
			array('site_sites_WdHooks', 'get_working_site'),

			'instancesof' => 'WdApplication'
		),

		'__get_working_site_id' => array
		(
			array('site_sites_WdHooks', 'get_working_site_id'),

			'instancesof' => 'WdApplication'
		)
	)
);