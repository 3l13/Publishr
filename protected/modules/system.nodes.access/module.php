<?php

class system_nodes_access_WdModule extends WdPModule
{
	protected function block_edit(array $properties, $permission)
	{
		return array
		(
			WdElement::T_CHILDREN => array
			(
				'title' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Titre',
						WdElement::T_MANDATORY => true
					)
				),

				'loginpageid' => new WdPageSelectorElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Page d\'autentification',
						WdElement::T_MANDATORY => true,

						'class' => 'list'
					)
				)
			)
		);
	}
}