<?php

/*
$offerEl = null;
$offerid = isset($_GET['offer']) ? $_GET['offer'] : null;

if ($offerid)
{
	global $core;

	$offerEl = new WdElement
	(
		WdElement::E_TEXT, array
		(
			WdForm::T_LABEL => 'Offre',

			'value' => $core->getModule('jobs.offers')->model()->load($offerid)->title
		)
	);
}

var_dump($offerid);
*/

return array
(
	'class' => 'Wd2CForm',

	'tags' => array
	(
		WdElement::T_CHILDREN => array
		(
			$offerEl,

			'gender' => new WdElement
			(
				WdElement::E_RADIO_GROUP, array
				(
					WdForm::T_LABEL => 'Civilité',
					WdElement::T_OPTIONS => array('Mme', 'Mlle', 'M'),
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
					WdForm::T_LABEL => 'Société'
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

			'message' => new WdElement
			(
				'textarea', array
				(
					WdForm::T_LABEL => 'Votre message'
				)
			)
		)
	),

	'messageComplete' => '<p>Votre message a été envoyé.</p>',

	'mailer' => array
	(
		WdMailer::T_DESTINATION => 'olaviale@hima360.com',
		WdMailer::T_BCC => 'olaviale@hima360.com',
		WdMailer::T_FROM => 'Contact Grandvatel <no-reply@grandvatel.com>',
		WdMailer::T_SUBJECT => 'Grandvatel : Formulaire de contact',
		WdMailer::T_TYPE => 'plain'
	),

	'template' => <<<EOT

Un message a été posté depuis le formulaire de contact :

Nom : #{@gender.index('Mme', 'Mlle', 'M')} #{@lastname} #{@firstname}
<wdp:if test="@company">Société : #{@company}</wdp:if>
E-Mail : #{@email}

Message : #{@message}

EOT
,

	'finalize' => 'email'
);