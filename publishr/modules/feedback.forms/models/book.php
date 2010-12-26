<?php

return array
(
	'class' => 'Wd2CForm',

	'tags' => array
	(
		WdElement::T_CHILDREN => array
		(
			'lastname' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Nom',
					WdElement::T_REQUIRED => true
				)
			),

			'firstname' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Prénom',
					WdElement::T_REQUIRED => true
				)
			),

			'phone' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Téléphone'
				)
			),

			'email' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'E-Mail',
					WdElement::T_REQUIRED => true,
					WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email'))
				)
			),

			'people' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Nombre de participants',
					WdElement::T_REQUIRED => true
				)
			),

			'hour' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Horaires',
					WdElement::T_REQUIRED => true
				)
			),

			'date' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Date de réservation',
					WdElement::T_REQUIRED => true
				)
			),

			'message' => new WdElement
			(
				'textarea', array
				(
					WdForm::T_LABEL => 'Votre message'
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
					'from' => 'Réservation <no-reply@grandvatel.com>',
					'subject' => 'Formulaire de réservation',
					'template' => <<<EOT
Un message a été posté depuis le formulaire de réservation :

Nom : #{@lastname} #{@firstname}
<wdp:if test="@phone">Téléphone : #{@phone}</wdp:if>
E-Mail : #{@email}

Nombre de participants : #{@people}
Date de réservation : #{@date} - #{@hour}

<wdp:if test="@message">Message : #{@message}</wdp:if>
EOT
				)
			)
		)
	)
);