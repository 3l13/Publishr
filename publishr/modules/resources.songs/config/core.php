<?php

$_includes_root = $root . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'resources_songs_WdMarkups' => $root . 'markups.php',

		'WdMP3Reader' => $_includes_root . 'wdmp3reader.php'
	)
);