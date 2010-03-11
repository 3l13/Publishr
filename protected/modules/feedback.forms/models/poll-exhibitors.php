<?php

$bmin = array
(
	1 => 'Très bonne',
	2 => 'Bonne',
	3 => 'Insuffisante',
	4 => 'Ne se prononce pas'
);

return array
(
	'class' => 'WdDividedForm',

	'tags' => array
	(
		WdElement::T_CHILDREN => array
		(
			'item[1]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'À combien de congrès participez-vous sur une année',
					WdElement::T_OPTIONS => array
					(
						1 => 'de 1 à 5',
						2 => 'de 6 à 10',
						3 => 'plus de 10'
					),

					WdElement::T_MANDATORY => true
				)
			),

			'item[2]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Qualité des stands',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[3]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Satisfaction de l’emplacement du stand',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[4]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Satisfaction sur le nombre de contacts rencontrés',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[5]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Satisfaction sur la qualité des contacts rencontrés',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[6]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Satisfaction sur le retour sur investissement',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[7]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Qualité des relations avec notre service Promotion et Partenariat',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[8]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Réactivité des interlocuteurs sur des demandes spécifiques',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[9]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Clarté du dossier technique',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[10]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Exhaustivité des informations transmises en amont du congrès',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[11]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Reviendrez-vous à ce congrès ?',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[12]' => new WdElement
			(
				'textarea', array
				(
					WdForm::T_LABEL => 'Suggestion d’amélioration'
				)
			),

			'item[13]' => new WdElement
			(
				'select', array
				(
					WdForm::T_LABEL => 'Note de satisfaction globale',
					WdElement::T_DESCRIPTION => 'de 1 à 10, 10 étant la meilleure note',
					WdElement::T_VALIDATOR => array(array('WdForm', 'validate_range'), array(1, 10)),
					WdElement::T_OPTIONS => array
					(
						null => '', 1 => 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
					)
				)
			),

			'item[14]' => new WdElement
			(
				'textarea', array
				(
					WdForm::T_LABEL => 'Remarques',

					'rows' => 5
				)
			),

			'identifier' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => 'Votre adresse e-mail',
					WdElement::T_MANDATORY => true,
					WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email'))
				)
			)
		)
	),

	'finalize' => array('feedback_polls_WdModule', 'event_vote')
);