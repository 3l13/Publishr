<?php

class contents_agenda_WdModule extends contents_WdModule
{
	protected function block_manage()
	{
		return new contents_articles_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'uid', 'date', 'finish', 'is_online'
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_CHILDREN => array
				(
					'date' => new WdDateElement
					(
						array
						(
							WdForm::T_LABEL => 'Date',
							WdElement::T_MANDATORY => true,
							WdElement::T_GROUP => 'date',
						)
					),

					'finish' => new WdDateElement
					(
						array
						(
							WdForm::T_LABEL => 'Date de fin',
							WdElement::T_GROUP => 'date',
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
			WdElement::T_CHILDREN => array
			(
				$base . '[homeLimit]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Limite du nombre d\'entrées sur la page d\'accueil',
						WdElement::T_DEFAULT => 2,
						WdElement::T_GROUP => 'url'
					)
				),

				$base . '[headLimit]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Limite du nombre d\'entrées sur la page de liste',
						WdElement::T_DEFAULT => 10,
						WdElement::T_GROUP => 'url'
					)
				)
			)
		);
	}

	protected function block_head()
	{
		return Patron(file_get_contents('views/head.html', true));
	}

	protected function block_view()
	{
		return Patron(file_get_contents('views/view.html', true));
	}
}