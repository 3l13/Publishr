<?php

$assets = array('css' => $path . 'public/page.css');

return array
(
	'/home' => array
	(
		'title' => "Accueil des articles",
		'provider' => true
	),

	'/list' => array
	(
		'title' => "Liste des articles",
		'provider' => true,
		'assets' => $assets
	),

	'/view' => array
	(
		'title' => "Détail d'un article",
		'provider' => true,
		'assets' => $assets
	),
);