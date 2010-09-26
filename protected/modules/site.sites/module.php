<?php

// http://labs.apache.org/webarch/uri/rfc/rfc3986.html

class site_sites_WdModule extends WdPModule
{
	protected function block_manage()
	{
		return new site_sites_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array('title', 'url', 'language')
			)
		);
	}

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

				'path' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Chemin'
					)
				),

				'model' => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Modèle',
						WdElement::T_OPTIONS => array
						(
							null => '<défaut>'
						)

						+ $this->get_site_models()
					)
				),

				'language' => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Langue',
						WdElement::T_OPTIONS => array
						(
							null => '',
							'en' => 'English',
							'es' => 'Espanol',
							'fr' => 'Français'
						),

						WdElement::T_MANDATORY => true
					)
				),

				'sourceid' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Source de traduction de ce site'
					)
				),

				'is_active' => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => 'Le site est actif'
					)
				)
			)
		);
	}

	public function get_site_models()
	{
		$models = array();

		$dh = opendir($_SERVER['DOCUMENT_ROOT'] . '/sites');

		while ($file = readdir($dh))
		{
			if ($file[0] == '.' || $file == 'all' || $file == 'default')
			{
				continue;
			}

			$models[] = $file;
		}

		if (!$models)
		{
			return $models;
		}

		sort($models);

		return array_combine($models, $models);
	}
}