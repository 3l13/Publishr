<?php

return array
(
	'autoload' => array
	(
		'feedback_comments_WdMarkups' => $root . 'markups.php',
		'feedback_comments_WdForm' => $root . 'includes/comment.form.php'
	),

	'classes aliases' => array
	(
		'Comment' => 'feedback_comments_WdActiveRecord'
	)
);