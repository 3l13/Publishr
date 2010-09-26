<?php

return array
(
	'/home' => array
	(
		'title' => 'Accueil des actualités',
		'assets' => array
		(
			'css' => 'public/base.css'
		)
	),

	'/list' => array
	(
		'title' => 'Liste des actualités',
		'assets' => array
		(
			'css' => 'public/base.css'
		)
	),

	'/view' => array
	(
		'title' => "Détail d'une actualité",
		'assets' => array
		(
			'css' => 'public/base.css'
		)
	),

	'/category' => array
	(
		'title' => "Liste des actualités pour une catégorie",
		'file' => 'list.html'
	)
);