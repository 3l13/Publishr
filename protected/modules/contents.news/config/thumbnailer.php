<?php

return array
(
	'newsHome' => array
	(
		array
		(
			'w' => 48,
			'h' => 48,
			'format' => 'png',
			'interlace' => true,
			'path' => '/repository/files/image/'
		),

		'module' => 'contents.news',
		'title' => 'Accueil',
		'description' => "Il s'agit de la miniature présente en page d'accueil, elle est utilisée pour illustrer l'actualité."
	),

	'newsList' => array
	(
		array
		(
			'w' => 64,
			'h' => 48,
			'format' => 'jpeg',
			'interlace' => true,
			'path' => '/repository/files/image/'
		),

		'module' => 'contents.news',
		'title' => 'Liste',
		'description' => "Il s'agit de la miniature présente en page de liste, elle est utilisée pour illustrer l'actualité."
	),

	'newsView' => array
	(
		array
		(
			'w' => 128,
			'h' => 96,
			'format' => 'jpeg',
			'interlace' => true,
			'path' => '/repository/files/image/'
		),

		'module' => 'contents.news',
		'title' => 'Affichée',
		'description' => "Il s'agit de la miniature présente en page d'affichage, elle est utilisée pour illustrer l'actualité."
	)
);