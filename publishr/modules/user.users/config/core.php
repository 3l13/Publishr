<?php

return array
(
	'autoload' => array
	(
		'user_users_WdMarkups' => $root . 'markups.php'
	),

	'config constructors' => array
	(
		'user' => array('merge', 'user')
	),

	'classes aliases' => array
	(
		'User' => 'user_users_WdActiveRecord'
	)
);