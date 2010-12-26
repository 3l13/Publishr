<?php

return array
(
	'manager.label' => array
	(
		'title' => 'Titre',
		'uid' => 'Utilisateur',
		'constructor' => 'Constructeur',
		'created' => 'Crée le',
		'modified' => 'Modifié le',
		'is_online' => 'En ligne'
	),

	'permission' => array
	(
		'modify associated site' => "Modifier le site d'appartenance"
	),

	'@operation.online.title' => 'Mettre en ligne',
	'@operation.online.confirm' => "Voulez-vous mettre l'entrée sélectionnée en ligne ?",
	'@operation.online.confirmN' => "Voulez-vous mettre les :count entrées sélectionnées en ligne ?",
	'@operation.online.do' => 'Mettre en ligne',
	'@operation.online.dont' => "Ne pas mettre en ligne",

	'@operation.offline.title' => 'Mettre hors ligne',
	'@operation.offline.confirm' => "Voulez-vous mettre l'entrée sélectionnée hors ligne ?",
	'@operation.offline.confirmN' => 'Voulez-vous mettre les :count entrées sélectionnées hors ligne ?',
	'@operation.offline.do' => 'Mettre hors ligne',
	'@operation.offline.dont' => "Ne pas mettre hors ligne"
);