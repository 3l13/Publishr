<?php

return array
(
	'objects.methods' => array
	(
		'__get_site' => array
		(
			array('site_sites_WdHooks', '__get_site'),

			'instanceof' => array('WdCore', 'system_nodes_WdActiveRecord')
		),

		'__get_site_id' => array
		(
			array('site_sites_WdHooks', '__get_site_id'),

			'instanceof' => 'WdCore'
		)
	)
);