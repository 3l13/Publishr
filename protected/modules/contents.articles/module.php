<?php

class contents_articles_WdModule extends contents_WdModule
{
	protected function block_edit(array $properties, $permission)
	{
		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_CHILDREN => array
				(
					contents_WdActiveRecord::DATE => new WdDateTimeElement
					(
						array
						(
							WdForm::T_LABEL => 'Date',
							WdElement::T_MANDATORY => true,
							WdElement::T_DEFAULT => date('Y-m-d H:i:s')
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