<?php

return array
(
	'class' => 'WdForm',

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
					WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email')),
					WdElement::T_DEFAULT => 'Votre e-mail',

					'onfocus' => 'this.value = ""',
					'onblur' => 'this.value = "Votre e-mail"'
				)
			),

			'#submit' => new WdElement
			(
				WdElement::E_SUBMIT, array
				(
					WdElement::T_WEIGHT => 1000,
					WdElement::T_INNER_HTML => 'Ok'
				)
			)
		),

		'id' => 'newsletter'
	),

	'config' => array
	(
		'config[destination]' => new WdElement
		(
			WdElement::E_TEXT, array
			(
				WdForm::T_LABEL => 'Adresse de destination',
				WdElement::T_GROUP => 'config',
				WdElement::T_DEFAULT => isset($user->email) ? $user->email : null
			)
		),

		'config' => new WdEMailNotifyElement
		(
			array
			(
				WdForm::T_LABEL => 'Paramètres du message électronique',
				WdElement::T_GROUP => 'config',
				WdElement::T_DEFAULT => array
				(
					'bcc' => isset($user->email) ? $user->email : null,
					'from' => 'Newsletter <no-reply@wdpublisher.com>',
					'subject' => 'Inscription Newsletter',
					'template' => <<<EOT
Inscription à la newsletter :

E-Mail : #{@email}

EOT
				)
			)
		)
	),

	'finalize' => 'email'
);