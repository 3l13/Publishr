<?php

$_includes_root = $root . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'WdAdjustImageElement' => $_includes_root . 'wdadjustimageelement.php',
		'WdPopImageElement' => $_includes_root . 'wdpopimageelement.php',
		'WdImagePreviewElement' => $_includes_root . 'wdimagepreviewelement.php',
		'WdImageSelectorElement' => $_includes_root . 'wdimageselectorelement.php',
		'WdImageUploadElement' => $_includes_root . 'wdimageuploadelement.php',

		'resources_images_WdManager' => $root . 'manager.php',
		'resources_images_WdManagerGallery' => $root . 'gallery.manager.php'
	)
);