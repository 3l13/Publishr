<?php

return array
(
	'autoload' => array
	(
		'feedback_comments_WdEvents' => $root . 'events.php',
		'feedback_comments_WdManager' => $root . 'manager.php',
		'feedback_comments_WdMarkups' => $root . 'markups.php'
	),

	'classes aliases' => array
	(
		'Comment' => 'feedback_comments_WdActiveRecord'
	)
);