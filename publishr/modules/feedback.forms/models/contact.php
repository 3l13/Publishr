<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
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
								WdForm::T_LABEL => '.Salutation',
								WdElement::T_OPTIONS => array('salutation.misses', 'salutation.miss', 'salutation.mister'),
								WdElement::T_REQUIRED => true
							)
						),

						'lastname' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => '.Lastname',
								WdElement::T_REQUIRED => true
							)
						),

						'firstname' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => '.Firstname',
								WdElement::T_REQUIRED => true
							)
						),

						'company' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => '.Company'
							)
						),

						'email' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => '.E-mail',
								WdElement::T_REQUIRED => true,
								WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email'))
							)
						),

						'message' => new WdElement
						(
							'textarea', array
							(
								WdForm::T_LABEL => '.Your message',
								WdElement::T_REQUIRED => true
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
		global $core;

		return array
		(
			'notify_destination' => $core->user->email,
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