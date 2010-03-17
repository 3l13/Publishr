<?php

class site_search_WdModule extends WdPModule
{
	protected function block_config($base)
	{
		return array
		(
			WdElement::T_CHILDREN => array
			(
				$base . '[url]' => new WdPageSelectorElement
				(
					'select', array
					(
						WdForm::T_LABEL => "Page sur laquelle s'effectue la recherche"
					)
				),

				$base . '[host]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Adresse du site",
						WdElement::T_LABEL => 'http://www.',
						WdElement::T_LABEL_POSITION => 'left'
					)
				)
			)
		);
	}
}