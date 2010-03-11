<?php

return array
(
	'tags' => array
	(
		WdElement::T_CHILDREN => array
		(
			'gender' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Civilité',
					WdElement::T_OPTIONS => array('Mme', 'Mlle', 'M'),
					WdElement::T_MANDATORY => true
				)
			),

			'email' =>  new WdElement
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

			'position' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Fonction'
				)
			)
		),

		'name' => 'newsletter'
	),

	'messageComplete' => 'E-Mail enregistré',

	'mailer' => array
	(
		WdMailer::T_DESTINATION => 'olaviale@hima360.com',
		WdMailer::T_BCC => 'olaviale@hima360.com',
		WdMailer::T_FROM => 'Newsletter EDF-UPSO <no-reply@uneriviereunterritoire.fr>',
		WdMailer::T_SUBJECT => 'EDF-UPSO : Newsletter',
		WdMailer::T_TYPE => 'plain'
	),

	'template' => <<<EOT

newsletter: #{@dump()}

EOT
,

	'finalize' => 'email'//array('newsletter_subscribers_WdModule', 'register')
);