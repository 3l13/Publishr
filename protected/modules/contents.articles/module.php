<?php

class contents_articles_WdModule extends contents_WdModule
{
	/*
	protected function control_operation_save(WdOperation $operation, $controls)
	{
		$params = &$operation->params;

		if (isset($params[Article::CONTENTS]) && is_array($params[Article::CONTENTS]))
		{
			$contents = $params[Article::CONTENTS];

			unset($params[Article::CONTENTS]);

			$params += $contents;
		}

		return parent::control_operation($operation, $controls);
	}
	*/

	protected function block_edit(array $properties, $permission)
	{
		global $registry;

		$default_editor = $registry->get($this . '.editor.default', 'moo');

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission),

			array
			(
				WdElement::T_CHILDREN => array
				(
					Article::CONTENTS => new WdMultiEditorElement
					(
						$properties['editor'] ? $properties['editor'] : $default_editor, array
						(
							WdForm::T_LABEL => 'Contents',
							WdElement::T_GROUP => 'contents',
							WdElement::T_MANDATORY => true
						)
					)
				)
			)
		);
	}

	protected function block_config($base)
	{
		return array
		(
			WdElement::T_GROUPS => array
			(
				'editor' => array
				(
					'title' => 'Éditeur'
				)
			),

			WdElement::T_CHILDREN => array
			(
				$base . '[url][month]' => new WdPageSelectorElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Page pour l\'affichage par mois',
						WdElement::T_GROUP => 'url'
					)
				),

				$base . '[url][category]' => new WdPageSelectorElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Page pour l\'affichage par catégorie',
						WdElement::T_GROUP => 'url'
					)
				),

				$base . '[url][author]' => new WdPageSelectorElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Page pour l\'affichage par auteur',
						WdElement::T_GROUP => 'url'
					)
				),

				$base . '[editor][default]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Éditeur par défaut',
						WdElement::T_GROUP => 'editor'
					)
				)
			)
		);
	}
}