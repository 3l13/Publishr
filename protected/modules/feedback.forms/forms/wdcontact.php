<?php

global $registry;

return array
(
	'class' => 'Wd2CForm',

	'tags' => array
	(
		WdElement::T_CHILDREN => array
		(
			'email' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'E-Mail',
					WdElement::T_MANDATORY => true,
					WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email'))
				)
			),

			'message' => new WdElement
			(
				'textarea', array
				(
					WdForm::T_LABEL => 'Message',
					WdElement::T_MANDATORY => true
				)
			)
		),

		'id' => 'contact'
	),

	'messageComplete' => $registry->get('forms.contact.complete'),

	'mailer' => $registry->get('forms.contact.') + array
	(
		WdMailer::T_TYPE => 'plain'
	),

	'template' => $registry->get('forms.contact.template'),

	'finalize' => 'email'
);