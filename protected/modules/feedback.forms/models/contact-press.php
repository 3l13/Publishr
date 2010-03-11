<?php

return array
(
	'class' => 'Wd2CForm',

	'tags' => array
	(
		WdElement::T_CHILDREN => array
		(
			'gender' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Gender',
					WdElement::T_OPTIONS => array('@genders.mrs', '@genders.miss', '@genders.mr'),
					WdElement::T_MANDATORY => true
				)
			),

			'lastname' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Lastname',
					WdElement::T_MANDATORY => true
				)
			),

			'firstname' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Firstname',
					WdElement::T_MANDATORY => true
				)
			),

			'media' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Média'
				)
			),

			'email' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'E-Mail',
					WdElement::T_MANDATORY => true,
					WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email'))
				)
			),

			'subject' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Subject',
					WdElement::T_MANDATORY => true
				)
			),

			'message' => new WdElement
			(
				'textarea', array
				(
					WdForm::T_LABEL => 'Your message'
				)
			)
		)
	),

	'finalize' => 'email',

	'config' => array
	(
		'config[destination]' => new WdElement
		(
			WdElement::E_TEXT, array
			(
				WdForm::T_LABEL => 'Addresse de destination',
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
					'from' => 'Contact <no-reply@wdpublisher.com>',
					'subject' => 'Formulaire de contact presse',
					'template' => <<<EOT
Un message a été posté depuis le formulaire de contact presse :

Nom : #{@gender.index('Mme', 'Mlle', 'M')} #{@lastname} #{@firstname}
<wdp:if test="@media">Média : #{@media}</wdp:if>
E-Mail : #{@email}

Message : #{@message}
EOT
				)
			)
		)
	)
);