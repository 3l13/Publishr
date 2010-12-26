<?php

return array
(
	'operation' => array
	(
		'title' => 'Perform operation',
		'continue' => 'Continue',
		'cancel' => 'Cancel',

		'confirm' => array
		(
			'one' => 'Are you sure you want to perform this operation on the selected entry?',
			'other' => 'Are you sure you want to perform this operation on the selected entries?'
		),

		'done' => 'Operation done'
	),

	'delete.operation' => array
	(
		'title' => 'Delete entries',
		'continue' => 'Delete',
		'cancel' => "Don't delete",

		'confirm' => array
		(
			'one' => 'Are you sure you want to permanently delete the selected entry?',
			'other' => 'Are you sure you want to permanently delete the :count selected entries?'
		)
	),

	'config.operation' => array
	(
		'done' => 'The configuration options have been saved.'
	),

	'label' => array
	(
		'language' => 'Language'
	),

	'manager' => array
	(
		'edit' => 'Edit the entry',
		'edit_named' => 'Edit the entry: :title'
	),

	'@manager.emptyCreateNew' => 'There is no entry: <strong><a href="!url">create a new entry…</a></strong>'
);