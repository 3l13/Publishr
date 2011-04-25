<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class quick_contact_WdForm extends Wd2CForm
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
						'email' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'E-Mail',
								WdElement::T_REQUIRED => true,
								WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email'))
							)
						),

						'message' => new WdElement
						(
							'textarea', array
							(
								WdForm::T_LABEL => 'Message',
								WdElement::T_REQUIRED => true
							)
						)
					)
				)
			),

			'table'
		);
	}

	static public function getConfig()
	{
		global $core;

		$email = $core->user->email;

		return array
		(
			WdElement::T_CHILDREN => array
			(
				'config[destination]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Addresse de destination',
						WdElement::T_GROUP => 'config',
						WdElement::T_DEFAULT => $email
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
							'from' => 'Contact <no-reply@wdpublisher.com>',
							'subject' => 'Formulaire de contact',
							'template' => <<<EOT
Un message a été posté depuis le formulaire de contact :

E-Mail : #{@email}

#{@message}
EOT
						)
					)
				)
			)
		);
	}
}