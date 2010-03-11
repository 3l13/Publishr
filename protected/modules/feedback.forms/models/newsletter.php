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

Nom : #{@gender.index('Mme', 'Mlle', 'M')} #{@lastname} #{@firstname}
<wdp:if test="@position">Poste : #{@position}</wdp:if>
E-Mail : #{@email}
EOT
				)
			)
		)
	),

	'finalize' => 'email'
);