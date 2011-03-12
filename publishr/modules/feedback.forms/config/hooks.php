<?php

return array
(
	'events' => array
	(
		'alter.editors.options' => array
		(
			array('feedback_forms_WdHooks', 'event_alter_editor_options')
		)
	),

	'patron.markups' => array
	(
		'feedback:form' => array
		(
			array('feedback_forms_WdMarkups', 'form'), array
			(
				'select' => array('required' => true)
			)
		)
	)
);