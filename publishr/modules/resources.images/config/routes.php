<?php

return array
(
	'manage' => array
	(

	),

	'/admin/resources.images/gallery' => array
	(
		'title' => 'Galerie',
		'block' => 'gallery',
		'workspace' => 'resources'
	),

	'create' => array
	(

	),

	'edit' => array
	(

	),

	'config' => array
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
	)
);