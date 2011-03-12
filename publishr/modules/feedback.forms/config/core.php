<?php

return array
(
	'autoload' => array
	(
		'feedback_forms_WdMarkups' => $root . 'markups.php',

		'WdFormSelectorElement' => $path . 'elements/form-selector.php',
		'form_WdEditorElement' => $path . 'elements/form-editor.php',

		'contact_WdForm' => $root . 'models/contact.php',
		'press_WdForm' => $root . 'models/contact-press.php',
		'quick_contact_WdForm' => $root . 'models/contact-quick.php'
	),

	'classes aliases' => array
	(
		'Form' => 'feedback_forms_WdActiveRecord'
	)
);