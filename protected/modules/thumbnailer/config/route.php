<?php

return array
(
	array
	(
		'/do/<module:[a-z0-9\.]+>/<nid:\d+>/thumbnail' => array
		(
			'callback' => array('thumbnailer_WdModule', 'operation_thumbnail')
		)
	)
);