<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_news_WdModule extends contents_WdModule
{
	protected function block_manage()
	{
		return new contents_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'uid', /*'category',*/ 'is_home_excluded', 'is_online', 'date', 'modified'
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
					contents_WdActiveRecord::DATE => new WdDateElement
					(
						array
						(
							WdForm::T_LABEL => 'Date',
							WdElement::T_REQUIRED => true,
							WdElement::T_DEFAULT => date('Y-m-d')
						)
					)
				)
			)
		);
	}

	/*
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
				$base . '[limits][home]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Limite du nombre d'entrées sur la page d'accueil",
						WdElement::T_DEFAULT => 3,
						WdElement::T_GROUP => 'limits'
					)
				),

				$base . '[limits][list]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Limite du nombre d'entrées sur la page de liste",
						WdElement::T_DEFAULT => 10,
						WdElement::T_GROUP => 'limits'
					)
				),

				$base . '[default_image]' => new WdPopImageElement
				(
					array
					(
						WdForm::T_LABEL => "Image par défaut",
						WdElement::T_GROUP => 'thumbnailer',
						WdElement::T_DESCRIPTION => "Il s'agit de l'image à utiliser lorsqu'aucune
						image n'est associée à l'entrée."
					)
				)
			)
		);
	}
	*/

	protected function block_head()
	{
		return Patron(file_get_contents('views/head.html', true));
	}

	protected function block_view()
	{
		return Patron(file_get_contents('views/view.html', true));
	}
}