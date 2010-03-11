<?php

$_includes_root = $root . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'WdFileUploadElement' => $_includes_root . 'wdfileuploadelement.php',

		'resources_files_WdManager' => $root . 'manager.php'
	),

	'classes aliases' => array
	(
		'File' => 'resources_files_WdActiveRecord'
	)
);