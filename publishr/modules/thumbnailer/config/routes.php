<?php

return array
(
	'/api/:module/:nid/thumbnail' => array
	(
		'class' => 'thumbnailer__thumbnail_WdOperation'
	),

	'/api/:module/:nid/thumbnails/:version' => array
	(
		'class' => 'thumbnailer__thumbnail_WdOperation'
	)
);