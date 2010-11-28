<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_agenda_WdModule extends contents_WdModule
{
	protected function block_manage()
	{
		return new contents_agenda_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'uid', 'is_home_excluded', 'is_online', 'date', 'finish', 'modified'
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
					'date' => null,

					new WdDateRangeElement
					(
						array
						(
							WdDateRangeElement::T_START_TAGS => array
							(
								WdElement::T_LABEL => 'Date de début',
								WdElement::T_REQUIRED => true,

								'name' => 'date'
							),

							WdDateRangeElement::T_FINISH_TAGS => array
							(
								WdElement::T_LABEL => 'Date de fin'
							)
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
				'limits' => array
				(
					'title' => 'Limites',
					'class' => 'form-section flat'
				)
			),

			WdElement::T_CHILDREN => array
			(
				$base . '[homeLimit]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Limite du nombre d\'entrées sur la page d\'accueil',
						WdElement::T_DEFAULT => 10,
						WdElement::T_GROUP => 'limits'
					)
				),

				$base . '[headLimit]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Limite du nombre d\'entrées sur la page de liste',
						WdElement::T_DEFAULT => 10,
						WdElement::T_GROUP => 'limits'
					)
				)
			)
		);
	}

	protected function block_head()
	{
		return Patron(file_get_contents('views/head.html', true, array('file' => dirname(__FILE__) . '/views/head.html')));
	}

	protected function block_view()
	{
		return Patron(file_get_contents('views/view.html', true, array('file' => dirname(__FILE__) . '/views/view.html')));
	}
}