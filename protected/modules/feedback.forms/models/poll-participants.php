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
					WdForm::T_LABEL => 'Êtes-vous un conférencier&nbsp;?',
					WdElement::T_MANDATORY => true,
					WdElement::T_OPTIONS => array
					(
						1 => 'Oui',
						0 => 'Non'
					)
				)
			),

			"<fieldset><legend><strong>Si oui</strong>, qu'avez-vous pensé de la&nbsp;&hellip;</legend>",

			'item[2]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Qualité de la preview',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[3]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Facilité de la soumission',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[4]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Qualité des relations avec le secrétariat de programme',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'</fieldset>',

			"<fieldset><legend>Qualité du lieu</legend>",

			'item[5]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Accessibilité',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[6]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Accueil hôtesses',
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'</fieldset>',

			'item[7]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => "Qualité de l'hôtel",
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[8]' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => "Nom de l'hôtel",
					WdElement::T_MANDATORY => true
				)
			),

			'item[9]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => "Accessibilité de l'hôtel",
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[10]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => "Facilité d'inscription",
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[11]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => "Qualité des intervenants",
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[12]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => "Qualité du programme social",
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[13]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => "Qualité des réponses obtenues à vos demandes auprès d’Europa Organisation",
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[14]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => "Qualité de l’exposition",
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[15]' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => "Qualité de la restauration",
					WdElement::T_OPTIONS => $bmin,
					WdElement::T_MANDATORY => true
				)
			),

			'item[16]' => new WdElement
			(
				'textarea', array
				(
					WdForm::T_LABEL => "Suggestion d'amélioration",

					'rows' => 5
				)
			),

			'item[17]' => new WdElement
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

			'item[18]' => new WdElement
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