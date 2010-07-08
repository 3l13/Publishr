<?php

class contents_news_WdModule extends contents_WdModule
{
	const OPERATION_HOME_INCLUDE = 'homeInclude';
	const OPERATION_HOME_EXCLUDE = 'homeExclude';

	protected function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_HOME_INCLUDE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_HOME_EXCLUDE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			)
		)

		+ parent::getOperationsAccessControls();
	}

	protected function operation_save(WdOperation $operation)
	{
		$operation->handle_booleans
		(
			array
			(
				'is_home_excluded'
			)
		);

		return parent::operation_save($operation);
	}

	protected function operation_homeInclude(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_home_excluded = false;
		$entry->save();

		wd_log_done('!title is now included on the home page', array('!title' => $entry->title));

		return true;
	}

	protected function operation_homeExclude(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_home_excluded = true;
		$entry->save();

		wd_log_done('!title is now excluded from the home page', array('!title' => $entry->title));

		return true;
	}

	protected function block_manage()
	{
		return new contents_news_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'uid', 'category', 'is_home_excluded', 'is_online', 'date', 'modified'
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
							WdElement::T_GROUP => 'date',
							WdElement::T_MANDATORY => true,
							WdElement::T_DEFAULT => date('Y-m-d')
						)
					),

					'imageid' => new WdPopImageElement
					(
						array
						(
							WdForm::T_LABEL => 'Image',
							WdElement::T_GROUP => 'contents'
						)
					),

					'is_home_excluded' => new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => "Ne pas afficher sur la page d'accueil",
							WdElement::T_GROUP => 'online'/*,
							WdElement::T_DESCRIPTION => "Cette option permet de définir la
							visibilité de l'entrée sur la page d'acceuil. Si la case est cochée
							l'entrée ne sera pas affichée sur la page d'accueil."*/
						)
					)
				)
			)
		);
	}

	protected function block_config($base)
	{
		$unes = array();
		$unes_rows = null;

		foreach (WdLocale::$languages as $language)
		{
			$unes[$base . '[une][' . $language . ']'] = new WdPopNodeElement
			(
				array
				(
					WdAdjustNodeElement::T_SCOPE => $this->id,
					WdForm::T_LABEL => $language,
					WdElement::T_GROUP => 'unes'
				)
			);

			$unes_rows .= '<tr><td class="label">{$' . $base . '[une][' . $language . '].label:}</td><td>{$' . $base . '[une][' . $language . ']}</td></tr>' . PHP_EOL;
		}

		return array
		(
			WdElement::T_GROUPS => array
			(
				'limits' => array
				(
					'title' => 'Limites'
				),

				'unes' => array
				(
					'title' => 'Une',
					'class' => 'form-section panel',
					'template' => '<table>' . $unes_rows . '</table>'
				)
			),

			WdElement::T_CHILDREN => array
			(
				$base . '[homeLimit]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Limite du nombre d'entrées sur la page d'accueil",
						WdElement::T_DEFAULT => 2,
						WdElement::T_GROUP => 'limits'
					)
				),

				$base . '[listLimit]' => new WdElement
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

			+ $unes
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