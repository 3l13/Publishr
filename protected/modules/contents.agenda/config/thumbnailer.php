<?php

return array
(
	'agendaHome' => array
	(
		array
		(
			'w' => 48,
			'h' => 48,
			'format' => 'png',
			'interlace' => true
		),

		'module' => 'contents.agenda',
		'title' => 'Accueil',
		'description' => "Il s'agit de la miniature présente en page d'accueil, elle est utilisée pour illustrer l'objet."
	),

	'agendaHead' => array
	(
		array
		(
			'w' => 64,
			'h' => 48,
			'format' => 'jpeg',
			'interlace' => true
		),

		'module' => 'contents.agenda',
		'title' => 'Liste',
		'description' => "Il s'agit de la miniature présente en page de liste, elle est utilisée pour illustrer l'objet."
	),

	'agendaView' => array
	(
		array
		(
			'w' => 128,
			'h' => 96,
			'format' => 'jpeg',
			'interlace' => true
		),

		'module' => 'contents.agenda',
		'title' => 'Affichée',
		'description' => "Il s'agit de la miniature présente en page d'affichage, elle est utilisée pour illustrer l'objet."
	)
);