<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_WdModule extends system_nodes_WdModule
{
	const OPERATION_HOME_INCLUDE = 'homeInclude';
	const OPERATION_HOME_EXCLUDE = 'homeExclude';

	protected function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_HOME_INCLUDE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_HOME_EXCLUDE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			)
		)

		+ parent::getOperationsAccessControls();
	}

	protected function operation_save(WdOperation $operation)
	{
		$operation->handle_booleans
		(
			array
			(
				'is_home_excluded'
			)
		);

		return parent::operation_save($operation);
	}

	protected function operation_homeInclude(WdOperation $operation)
	{
		$entry = $operation->entry;

		$entry->is_home_excluded = false;
		$entry->save();

		wd_log_done('!title is now included on the home page', array('!title' => $entry->title));

		return true;
	}

	protected function operation_homeExclude(WdOperation $operation)
	{
		$entry = $operation->entry;

		$entry->is_home_excluded = true;
		$entry->save();

		wd_log_done('!title is now excluded from the home page', array('!title' => $entry->title));

		return true;
	}

	protected function block_edit(array $properties, $permission)
	{
		global $registry;

		$default_editor = $registry->get(strtr($this->id, '.', '_') . '.editor.default', 'moo');

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission),

			array
			(
				WdElement::T_GROUPS => array
				(
					'contents' => array
					(
						'title' => 'Contenu',
						'class' => 'form-section flat'
					),

					'date' => array
					(
					)
				),

				WdElement::T_CHILDREN => array
				(
					contents_WdActiveRecord::SUBTITLE => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Sous-titre'
						)
					),

					contents_WdActiveRecord::CONTENTS => new WdMultiEditorElement
					(
						isset($properties['editor']) ? $properties['editor'] : $default_editor, array
						(
							WdElement::T_LABEL_MISSING => 'Contents',
							WdElement::T_GROUP => 'contents',
							WdElement::T_MANDATORY => true
						)
					),

					contents_WdActiveRecord::EXCERPT => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Accroche',
							WdElement::T_GROUP => 'contents',
							WdElement::T_DESCRIPTION => "L'arroche présente	en quelques mots
							le contenu. Vous pouvez saisir votre propre accroche ou laisser le
							système la créer pour vous.",

							'rows' => 3
						)
					),

					contents_WdActiveRecord::DATE => new WdDateElement
					(
						array
						(
							WdForm::T_LABEL => 'Date',
							WdElement::T_MANDATORY => true,
							WdElement::T_DEFAULT => date('Y-m-d')
						)
					),

					'is_home_excluded' => new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => "Ne pas afficher sur la page d'accueil",
							WdElement::T_GROUP => 'online'/*,
							WdElement::T_DESCRIPTION => "Cette option permet de définir la
							visibilité de l'entrée sur la page d'acceuil. Si la case est cochée
							l'entrée ne sera pas affichée sur la page d'accueil."*/
						)
					)
				)
			)
		);
	}

	protected function block_manage()
	{
		return new contents_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', /*'category',*/ 'uid', 'is_online', 'date', 'modified'
				)
			)
		);
	}
}