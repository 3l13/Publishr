<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contact_WdForm extends Wd2CForm
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

						'company' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Company'
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
								WdForm::T_LABEL => 'Your message',
								WdElement::T_MANDATORY => true
							)
						)
					)
				)
			),

			'div'
		);
	}

	static public function get_defaults()
	{
		global $app;

		return array
		(
			'notify_destination' => $app->user->email,
			'notify_from' => 'Contact <no-reply@' . preg_replace('#^www#', '', $_SERVER['HTTP_HOST']) .'>',
			'notify_subject' => 'Formulaire de contact',
			'notify_template' => <<<EOT
Un message a été posté depuis le formulaire de contact :

Nom : #{@gender.index('Mme', 'Mlle', 'M')} #{@lastname} #{@firstname}
<wdp:if test="@company">Société : #{@company}</wdp:if>
E-Mail : #{@email}

Message : #{@message}
EOT
		);
	}
}