<?php

return array
(
	'/api/:module/:nid/thumbnail' => array
	(
		'callback' => array('thumbnailer_WdModule', 'operation_thumbnail')
	)
);