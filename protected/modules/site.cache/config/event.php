<?php

return array
(
	'operation.save' => array('m:publisher.cache', 'clear'),
	'operation.delete' => array('m:publisher.cache', 'clear'),
	'operation.online' => array('m:publisher.cache', 'clear'),
	'clear publisher cache' => array('m:publisher.cache', 'clear')
);