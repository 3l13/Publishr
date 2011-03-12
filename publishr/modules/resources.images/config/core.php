<?php

$widgets_path = $path . 'widgets' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'WdAdjustImageWidget' => $widgets_path . 'adjust-image.php',
		'WdPopImageWidget' => $widgets_path . 'pop-image.php',
		'WdImageUploadElement' => $widgets_path . 'image-upload.php',
		'WdAdjustThumbnailWidget' => $widgets_path . 'adjust-thumbnail.php',

		'resources_images_WdManagerGallery' => $path . 'gallery.manager.php'
	)
);