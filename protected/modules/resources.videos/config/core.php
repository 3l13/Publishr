<?php

$_includes_root = $root . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'resources_videos_WdManager' => $root . 'manager.php',

		'AMF0Parser' => $_includes_root . 'amf0parser',
		'Flvinfo' => $_includes_root . 'flvinfo.php',
		'WdVideoUploadElement' => $_includes_root . 'wdvideouploadelement.php'
	),

	'classes aliases' => array
	(
		'Video' => 'resources_videos_WdActiveRecord'
	)
);