<?php

return array
(
	'autoload' => array
	(
		'WdFileUploadElement' => $root . 'elements/fileupload.element.php',

		'resources_files_WdManager' => $root . 'manager.php'
	),

	'classes aliases' => array
	(
		'File' => 'resources_files_WdActiveRecord'
	)
);