<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class user_members_WdModule extends user_users_WdModule
{
	protected $accept = array
	(
		'image/gif',
		'image/jpeg',
		'image/png'
	);

	protected function validate_operation_save(WdOperation $operation)
	{
		$file = new WdUploaded('photo', $this->accept, false);

		if ($file)
		{
			if ($file->er)
			{
				$operation->form->log
				(
					'photo', 'Unable to upload file %file: :message.', array
					(
						'%file' => $file->name,
						':message' => $file->er_message
					)
				);

				return false;
			}

			if ($file->location)
			{
				$operation->params['photo'] = $file;
			}
		}

		return parent::validate_operation_save($operation);
	}

	protected function block_edit(array $properties, $permission)
	{
		return array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_GROUPS => array
				(
					'numbers' => array
					(
						'title' => 'Numéros de téléphone',
						'class' => 'form-section flat',
						'template' => <<<EOT
<table class="panel">
<tr><td class="label">{\$number_work.label:}</td><td>{\$number_work}</td>
<td class="label">{\$number_fax.label:}</td><td>{\$number_fax}</td></tr>
<tr><td class="label">{\$number_home.label:}</td><td>{\$number_home}</td>
<td class="label">{\$number_pager.label:}</td><td>{\$number_pager}</td></tr>
<tr><td class="label">{\$number_mobile.label:}</td><td>{\$number_mobile}</td><td colspan="2">&nbsp;</td></tr>
</table>
EOT
					),

					'private' => array
					(
						'title' => 'Données privées',
						'class' => 'form-section flat',
						'template' => <<<EOT
<table class="panel">
<tr><td class="label">{\$address.label:}</td><td colspan="3">{\$address}</td></tr>
<tr><td>&nbsp;</td><td colspan="3">{\$address_complement}</td></tr>
<tr><td class="label">{\$city.label:}</td><td colspan="3">{\$city}</td></tr>
<tr><td class="label">{\$state.label:}</td><td>{\$state}</td>
<td class="label">{\$postalcode.label:}</td><td>{\$postalcode}</td></tr>
<tr><td class="label">{\$country.label:}</td><td colspan="3">{\$country}</td></tr>
<tr><td class="label">{\$webpage.label:}</td><td colspan="3">{\$webpage}</td></tr>
<tr><td class="label">{\$birthday.label:}</td><td colspan="3">{\$birthday}</td></tr>
</table>
EOT
					),

					'professional' => array
					(
						'title' => 'Données professionnelles',
						'class' => 'form-section flat',
						'template' => <<<EOT
<table class="panel">
<tr><td class="label">{\$position.label:}</td><td>{\$position}</td>
<td class="label">{\$service.label:}</td><td>{\$service}</td></tr>
<tr><td class="label">{\$company.label:}</td><td colspan="3">{\$company}</td></tr>
<tr><td class="label">{\$company_address.label:}</td><td colspan="3">{\$company_address}</td></tr>
<tr><td>&nbsp;</td><td colspan="3">{\$company_address_complement}</td></tr>
<tr><td class="label">{\$company_city.label:}</td><td colspan="3">{\$company_city}</td></tr>
<tr><td class="label">{\$company_state.label:}</td><td>{\$company_state}</td>
<td class="label">{\$company_postalcode.label:}</td><td>{\$company_postalcode}</td></tr>
<tr><td class="label">{\$company_country.label:}</td><td colspan="3">{\$company_country}</td></tr>
<tr><td class="label">{\$company_webpage.label:}</td><td colspan="3">{\$company_webpage}</td></tr>
</table>
EOT
					),

					'misc' => array
					(
						'title' => 'Informations complémentaires',
						'class' => 'form-section flat',
						'template' => <<<EOT
<table class="panel">
<tr><td class="label">{\$misc1.label:}</td><td>{\$misc1}</td></tr>
<tr><td class="label">{\$misc2.label:}</td><td>{\$misc2}</td></tr>
<tr><td class="label">{\$misc3.label:}</td><td>{\$misc3}</td></tr>
<tr><td class="label">{\$misc4.label:}</td><td>{\$misc4}</td></tr>
<tr><td class="label" style="vertical-align: top">{\$notes.label:}</td><td>{\$notes}</td></tr>
</table>
EOT
					),

					'attached' => array
					(
						'title' => 'Pièces attachées',
						'class' => 'form-section flat'
					)
				),

				WdElement::T_CHILDREN => array
				(
					'gender' => new WdElement
					(
						'select', array
						(
							WdForm::T_LABEL => 'Civilité',
							WdElement::T_REQUIRED => true,
							WdElement::T_GROUP => 'contact',
							WdElement::T_WEIGHT => -10,
							WdElement::T_OPTIONS => array
							(
								null => '',
								t('@gender.misses'),
								t('@gender.miss'),
								t('@gender.mister')
							)
						)
					),

					#
					# numbers
					#

					'number_work' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Travail',
							WdElement::T_GROUP => 'numbers'
						)
					),

					'number_home' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Domicile',
							WdElement::T_GROUP => 'numbers'
						)
					),

					'number_fax' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'FAX',
							WdElement::T_GROUP => 'numbers'
						)
					),

					'number_pager' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Pager',
							WdElement::T_GROUP => 'numbers'
						)
					),

					'number_mobile' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Mobile',
							WdElement::T_GROUP => 'numbers'
						)
					),

					#
					# private
					#

					'address' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Adresse',
							WdElement::T_GROUP => 'private'
						)
					),

					'address_complement' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Complément d\'Adresse',
							WdElement::T_GROUP => 'private'
						)
					),

					'city' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Ville/Localité',
							WdElement::T_GROUP => 'private'
						)
					),

					'state' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'État/Province',
							WdElement::T_GROUP => 'private'
						)
					),

					'postalcode' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Code postal',
							WdElement::T_GROUP => 'private'
						)
					),

					'country' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Pays',
							WdElement::T_GROUP => 'private'
						)
					),

					'webpage' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Page Web',
							WdElement::T_GROUP => 'private'
						)
					),

					'birthday' => new WdDateElement
					(
						array
						(
							WdForm::T_LABEL => 'Date de naissance',
							WdElement::T_GROUP => 'private'
						)
					),

					#
					# professional
					#

					'position' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Poste',
							WdElement::T_GROUP => 'professional'
						)
					),

					'service' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Service',
							WdElement::T_GROUP => 'professional'
						)
					),

					'company' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Société',
							WdElement::T_GROUP => 'professional'
						)
					),

					'company_address' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Adresse',
							WdElement::T_GROUP => 'professional'
						)
					),

					'company_address_complement' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Complément d\'adresse',
							WdElement::T_GROUP => 'professional'
						)
					),

					'company_city' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Ville/Localité',
							WdElement::T_GROUP => 'professional'
						)
					),

					'company_state' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'État/Province',
							WdElement::T_GROUP => 'professional'
						)
					),

					'company_postalcode' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Code postal',
							WdElement::T_GROUP => 'professional'
						)
					),

					'company_country' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Pays',
							WdElement::T_GROUP => 'professional'
						)
					),

					'company_webpage' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Page Web',
							WdElement::T_GROUP => 'professional'
						)
					),

					#
					# miscelaneous informations
					#

					'misc1' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Divers 1',
							WdElement::T_GROUP => 'misc'
						)
					),

					'misc2' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Divers 2',
							WdElement::T_GROUP => 'misc'
						)
					),

					'misc3' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Divers 3',
							WdElement::T_GROUP => 'misc'
						)
					),

					'misc4' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Divers 4',
							WdElement::T_GROUP => 'misc'
						)
					),

					'notes' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Notes',
							WdElement::T_GROUP => 'misc'
						)
					),

					#
					# photo
					#

					'photo' => new WdElement
					(
						WdElement::E_FILE, array
						(
							WdForm::T_LABEL => 'Photo',
							WdElement::T_GROUP => 'attached',
							WdElement::T_FILE_WITH_LIMIT => 256,
							WdElement::T_FILE_WITH_REMINDER => true,
						)
					)
				)
			)
		);
	}
}