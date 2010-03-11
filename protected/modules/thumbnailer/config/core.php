<?php

$_includes_root = $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoconfig' => array
	(
		'thumbnailer' => 'thumbnailer_WdModule'
	),

	'autoload' => array
	(
		'thumbnailer_WdEvents' => $root . 'events.php',
		'WdThumbnailerConfigElement' => $_includes_root . 'wdthumbnailerconfigelement.php'
	)
);