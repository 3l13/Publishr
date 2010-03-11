<?php

return array
(
	'class' => 'atalian_contact_WdForm',

	'tags' => array
	(
		WdElement::T_CHILDREN => array
		(
//			'<h3>Votre demande concerne</h3>',

			'for' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdElement::T_OPTIONS => array
					(
						'Un devis',
						'De la documentation'
					)
				)
			),

			'people' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Nombre de personnes travaillant sur votre site',
					WdElement::T_MANDATORY => true
				)
			),

			'surface' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Surface (en m²) à traiter',
					WdElement::T_MANDATORY => true
				)
			),

//			'<h3>Votre besoin concerne <sup>*</sup></h3>',

			'about' => new WdElement
			(
				WdElement::E_CHECKBOX_GROUP, array
				(
					WdElement::T_OPTIONS => array
					(
						1 => 'La Propriété',
						'Le Bâtiment',
						'La Sécurité',
						'La Technique',
						'L\'Hygiène',
						'Les Espaces verts',
						'Transport',
						'Accueil'
					),

					'class' => 'list'
				)
			),

			'other' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Autre (précisez)'
				)
			),

//			'<h3>Vos coordonnées</h3>',

			'gender' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Civilité',
					WdElement::T_OPTIONS => array('Mlle', 'Mme', 'M'),
					WdElement::T_MANDATORY => true
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

			'address' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Adresse',
					WdElement::T_MANDATORY => true
				)
			),

			'zipcode' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Code Postal',
					WdElement::T_MANDATORY => true
				)
			),

			'city' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Ville',
					WdElement::T_MANDATORY => true
				)
			),

			'country' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Pays',
					WdElement::T_MANDATORY => true
				)
			),

			'phone' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Téléphone',
					WdElement::T_MANDATORY => true
				)
			),

			'email' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'E-Mail',
					WdElement::T_MANDATORY => true
				)
			),

			'message' => new WdElement
			(
				'textarea', array
				(
					WdForm::T_LABEL => 'Votre message'
				)
			),

			'company' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Société',
					WdElement::T_MANDATORY => true
				)
			)
		)
	),

	'config' => array
	(
		'config[destination]' => new WdElement
		(
			WdElement::E_TEXT, array
			(
				WdForm::T_LABEL => 'Addresse de destination',
				WdElement::T_GROUP => 'config',
				WdElement::T_DEFAULT => 'marie-jeanne.lemoux@atalian.com'
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
					'from' => 'Contact Atalian <no-reply@atalian.com>',
					'subject' => 'Atalian : Formulaire de contact',
					'template' => <<<EOT

Un message a été posté depuis le formulaire de contact :

Nom : #{@gender.index('Mlle', 'Mme', 'M')} #{@lastname} #{@firstname}

Société : #{@company}

Adresse : #{@address}, #{@zipcode} #{@city} #{@country}

Téléphone : #{@phone}

E-Mail : #{@email}

<wdp:if test="@message">Message : #{@message}</wdp:if>

Besoin conserné :<wdp:if test="@about.1"> La propreté</wdp:if><wdp:if test="@about.2"> Le Bâtiment</wdp:if><wdp:if test="@about.3"> La sécurité</wdp:if><wdp:if test="@about.4"> La Technique</wdp:if><wdp:if test="@about.5"> L'Hygiène</wdp:if><wdp:if test="@about.6"> Les Espaces Verts</wdp:if><wdp:if test="@about.7"> Transport</wdp:if><wdp:if test="@about.8"> Accueil</wdp:if>

<wdp:if test="@other">
Autre : #{@other}
</wdp:if>

<wdp:choose>
<wdp:when test="@for.equals(1)">
La demande concerne de la documentation</wdp:when>
<wdp:otherwise>
La demande concerne un devis pour #{@people} personnes et une surface de #{@surface}m2.</wdp:otherwise>
</wdp:choose>

EOT
				)
			)
		)
	),

	'finalize' => 'email'
);