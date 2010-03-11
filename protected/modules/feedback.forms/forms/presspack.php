<?php

return array
(
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

			'lastname' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Nom',
					WdElement::T_MANDATORY => true
				)
			),

			'firstname' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Prénom',
					WdElement::T_MANDATORY => true
				)
			),

			'company' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Société',
					WdElement::T_MANDATORY => true
				)
			),

			'position' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Position',
					WdElement::T_MANDATORY => true
				)
			),

			new WdElement
			(
				WdElement::E_SUBMIT, array
				(
					WdElement::T_INNER_HTML => 'Envoyer'
				)
			)
		)
	),

	'messageComplete' =>

		'Merci',

	'finalize' => array('press_presspack_WdModule', 'download')
);