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
	protected function operation_save(WdOperation $operation)
	{
		$operation->handle_booleans(array('is_home_excluded'));

		return parent::operation_save($operation);
	}

	/**
	 * The 'home_include' operation is used to include a node is the home list.
	 */

	const OPERATION_HOME_INCLUDE = 'home_include';

	protected function get_operation_home_include_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_MAINTAIN,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_home_include(WdOperation $operation)
	{
		$entry = $operation->entry;

		$entry->is_home_excluded = false;
		$entry->save();

		wd_log_done('!title is now included on the home page', array('!title' => $entry->title));

		return true;
	}

	/**
	 * The `home_exclude` operation is used to exclude a node from the home list.
	 */

	const OPERATION_HOME_EXCLUDE = 'home_exclude';

	protected function get_operation_home_exclude_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_MAINTAIN,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_home_exclude(WdOperation $operation)
	{
		$entry = $operation->entry;

		$entry->is_home_excluded = true;
		$entry->save();

		wd_log_done('!title is now excluded from the home page', array('!title' => $entry->title));

		return true;
	}

	protected function block_manage()
	{
		return new contents_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', /*'category',*/ 'uid', 'is_home_excluded', 'is_online', 'date', 'modified'
				)
			)
		);
	}

	protected function block_config()
	{
		return array
		(
			WdElement::T_GROUPS => array
			(
				'limits' => array
				(
					'title' => 'Limites',
					'class' => 'form-section flat'
				)
			),

			WdElement::T_CHILDREN => array
			(
				"local[$this->flat_id.default_editor]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Éditeur par défaut"
					)
				),

				"local[$this->flat_id.use_multi_editor]" => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => "Permettre à l'utilisateur de changer d'éditeur"
					)
				),

				"local[$this->flat_id.limits.home]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Limite du nombre d'entrées sur la page d'accueil",
						WdElement::T_DEFAULT => 3,
						WdElement::T_GROUP => 'limits'
					)
				),

				"local[$this->flat_id.limits.list]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Limite du nombre d'entrées sur la page de liste",
						WdElement::T_DEFAULT => 10,
						WdElement::T_GROUP => 'limits'
					)
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $core;

		$default_editor = $core->working_site->metas->get($this->flat_id . '.default_editor', 'moo');
		$use_multi_editor = $core->working_site->metas->get($this->flat_id . '.use_multi_editor');

		if ($use_multi_editor)
		{

		}
		else
		{

		}

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

					contents_WdActiveRecord::BODY => new WdMultiEditorElement
					(
						$properties['editor'] ? $properties['editor'] : $default_editor, array
						(
							WdElement::T_LABEL_MISSING => 'Contents',
							WdElement::T_GROUP => 'contents',
							WdElement::T_REQUIRED => true,

							'rows' => 16
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
							système la créer pour vous à partir des 50 premiers mots du contenu.",

							'rows' => 3
						)
					),

					contents_WdActiveRecord::DATE => new WdDateElement
					(
						array
						(
							WdForm::T_LABEL => 'Date',
							WdElement::T_REQUIRED => true,
							WdElement::T_DEFAULT => date('Y-m-d')
						)
					),

					'is_home_excluded' => new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => "Ne pas afficher en page d'accueil",
							WdElement::T_GROUP => 'online',
							WdElement::T_DESCRIPTION => "L'entrée n'apparait pas en page d'accueil
							lorsque la case est cochée. Que la case soit cochée ou non, l'entrée
							apparait en page de liste."
						)
					)
				)
			)
		);
	}
}