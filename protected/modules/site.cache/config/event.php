<?php

return array
(
	'publisher.publish:before' => array('m:site.cache', 'get'),

	'operation.save' => array('m:site.cache', 'clear'),
	'operation.delete' => array('m:site.cache', 'clear'),
	'operation.online' => array('m:site.cache', 'clear'),
	'operation.offline' => array('m:site.cache', 'clear'),
	'clear page cache' => array('m:site.cache', 'clear')
);