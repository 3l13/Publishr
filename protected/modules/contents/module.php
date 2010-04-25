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
	protected function control_operation_save(WdOperation $operation, $controls)
	{
		$params = &$operation->params;

		if (isset($params[contents_WdActiveRecord::CONTENTS]) && is_array($params[contents_WdActiveRecord::CONTENTS]))
		{
			$contents = $params[contents_WdActiveRecord::CONTENTS];

			unset($params[contents_WdActiveRecord::CONTENTS]);

			$params += $contents;
		}

		return parent::control_operation($operation, $controls);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $registry;

		$default_editor = $registry->get($this . '.editor.default', 'moo');

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission),

			array
			(
				WdElement::T_GROUPS => array
				(
					'contents' => array
					(

					),

					'date' => array
					(
					)
				),

				WdElement::T_CHILDREN => array
				(
					/*
					contents_WdActiveRecord::CONTENTS => new WdMultiEditorElement
					(
						$properties['editor'] ? $properties['editor'] : $default_editor, array
						(
							WdForm::T_LABEL => 'Contents',
							WdElement::T_GROUP => 'contents',
							WdElement::T_MANDATORY => true
						)
					),
					*/

					contents_WdActiveRecord::CONTENTS => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Contents',
							WdElement::T_GROUP => 'contents',
							WdElement::T_MANDATORY => true
						)
					),

					contents_WdActiveRecord::EXCERPT => new WdElement
					(
						'textarea', array
						(
							WdForm::T_LABEL => 'Accroche',
							WdElement::T_GROUP => 'contents',
							WdElement::T_DESCRIPTION => "L'arroche présente	en quelques mots
							l'article. Vous pouvez saisir votre propre accroche ou laisser le
							système la créer pour vous.",

							'rows' => 3
						)
					),

					contents_WdActiveRecord::DATE => new WdDateTimeElement
					(
						array
						(
							WdForm::T_LABEL => 'Date',
							WdElement::T_GROUP => 'date',
							WdElement::T_MANDATORY => true,
							WdElement::T_DEFAULT => date('Y-m-d H:i:s')
						)
					)
				)
			)
		);
	}

	protected function block_manage()
	{
		return new contents_articles_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'category', 'uid', 'is_online', 'date'
				)
			)
		);
	}
}