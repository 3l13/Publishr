<?php

return array
(
	'autoload' => array
	(
		'feedback_forms_WdMarkups' => $root . 'markups.php',
		'feedback_forms_WdManager' => $root . 'manager.php',

		'WdFormSelectorElement' => $root . 'includes' . DIRECTORY_SEPARATOR . 'wdformselectorelement.php',

		'press_WdForm' => $root . 'models/contact-press.php',
		'quick_contact_WdForm' => $root . 'models/contact-quick.php'
	),

	'classes aliases' => array
	(
		'Form' => 'feedback_forms_WdActiveRecord'
	)
);