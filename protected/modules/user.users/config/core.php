<?php

return array
(
	'autoload' => array
	(
		'user_users_WdManager' => $root . 'manager.php',
		'user_users_WdMarkups' => $root . 'markups.php'
	),

	'classes aliases' => array
	(
		'User' => 'user_users_WdActiveRecord'
	)
);