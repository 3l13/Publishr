<?php

class site_WdModule extends WdPModule
{
	protected function block_config($base)
	{
		return array
		(
			WdElement::T_CHILDREN => array
			(
				'site[base]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'URL de base du site <span class="small">(site.base)</span>'
					)
				)
			)
		);
	}
}
