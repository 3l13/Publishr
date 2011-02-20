<?php

return array
(
	'manage' => array
	(

	),

	'/admin/resources.images/gallery' => array
	(
		'title' => '.gallery',
		'block' => 'gallery',
		'workspace' => 'resources'
	),

	'create' => array
	(

	),

	'config' => array
	(

	),

	'edit' => array
	(

	),

	'/admin/resources' => array
	(
		'location' => '/admin/resources.images'
	),

	'/api/components/adjustimage' => array
	(
		'callback' => array('WdAdjustImageElement', 'operation_get')
	),

	'/api/components/adjustimage/results' => array
	(
		'callback' => array('WdAdjustImageElement', 'operation_results')
	),

	'/api/components/adjustimage/popup' => array
	(
		'callback' => array('WdAdjustImageElement', 'operation_popup'),
		'controls' => array
		(
			WdModule::CONTROL_AUTHENTICATION => true
		)
	),

	'/api/components/adjustthumbnail/popup' => array
	(
		'callback' => array('WdAdjustThumbnailElement', 'operation_popup'),
		'controls' => array
		(
			WdModule::CONTROL_AUTHENTICATION => true
		)
	)
);