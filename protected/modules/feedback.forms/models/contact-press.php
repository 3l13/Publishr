<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class press_WdForm extends Wd2CForm
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			wd_array_merge_recursive
			(
				$tags, array
				(
					WdElement::T_CHILDREN => array
					(
						'gender' => new WdElement
						(
							WdElement::E_RADIO_GROUP, array
							(
								WdForm::T_LABEL => 'Gender',
								WdElement::T_OPTIONS => array('salutation.misses', 'salutation.miss', 'salutation.mister'),
								WdElement::T_REQUIRED => true
							)
						),

						'lastname' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Lastname',
								WdElement::T_REQUIRED => true
							)
						),

						'firstname' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Firstname',
								WdElement::T_REQUIRED => true
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
								WdElement::T_REQUIRED => true,
								WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email'))
							)
						),

						'subject' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Subject',
								WdElement::T_REQUIRED => true
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
				)
			)
		);
	}

	static public function get_defaults()
	{
		global $core;

		return array
		(
			'notify_destination' => $core->user->email,
			'notify_bcc' => $core->user->email,
			'notify_from' => 'Contact <no-reply@wdpublisher.com>',
			'notify_subject' => 'Formulaire de contact presse',
			'notify_template' => <<<EOT
Un message a été posté depuis le formulaire de contact presse :

Nom : #{@gender.index('Mme', 'Mlle', 'M')} #{@lastname} #{@firstname}
Média : #{@media.or('N/C')}
E-Mail : #{@email}

Message :

#{@message}
EOT
		);
	}
}