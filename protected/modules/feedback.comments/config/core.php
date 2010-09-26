<?php

return array
(
	'autoload' => array
	(
		'feedback_comments_WdEvents' => $root . 'events.php',
		'feedback_comments_WdHooks' => $root . 'hooks.php',
		'feedback_comments_WdManager' => $root . 'manager.php',
		'feedback_comments_WdMarkups' => $root . 'markups.php',

		'feedback_comments_WdForm' => $root . 'includes/comment.form.php'
	),

	'classes aliases' => array
	(
		'Comment' => 'feedback_comments_WdActiveRecord'
	)
);