<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// http://labs.apache.org/webarch/uri/rfc/rfc3986.html

class site_sites_WdModule extends WdPModule
{
	public function update_cache()
	{
		$filename = $_SERVER['DOCUMENT_ROOT'] . WdCore::$config['repository.cache'] . '/core/sites';

		if (!is_writable(dirname($filename)))
		{
			wd_log('File %filename is not writable', array('%filename' => $filename));

			return;
		}

		$sites = $this->model->all;

		$data = serialize($sites);

		file_put_contents($filename, $data);
	}

	protected function block_manage()
	{
		return new site_sites_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array('title', 'url', 'language', 'status')
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $document;

		$document->css->add('public/edit.css');

		$translation_sources_el = null;
		$translation_sources_options = $this->model
		->select('siteid, concat(title, ":", language) title')
		->where('siteid != ?', (int) $properties['siteid'])
		->pairs;

		if ($translation_sources_options)
		{
			$translation_sources_el = new WdElement
			(
				'select', array
				(
					WdElement::T_LABEL => 'Source de traduction',
					WdElement::T_LABEL_POSITION => 'before',
					WdElement::T_GROUP => 'i18n',
					WdElement::T_OPTIONS => array(0 => '<aucune>') + $translation_sources_options
				)
			);
		}

		return array
		(
			WdElement::T_GROUPS => array
			(
				'location' => array
				(
					'title' => 'Emplacement',
					'class' => 'form-section flat location'
				),

				'i18n' => array
				(
					'title' => 'Internationalisation',
					'class' => 'form-section flat'
				),

				'visibility' => array
				(
					'title' => 'Visibilité',
					'class' => 'form-section flat'
				)
			),

			WdElement::T_CHILDREN => array
			(
				'title' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Titre',
						WdElement::T_REQUIRED => true
					)
				),

				'admin_title' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Titre administratif',
						WdElement::T_DESCRIPTION => "Il s'agit du titre utilisé par l'interface d'administration."
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

				'subdomain' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Sous-domaine',
						WdElement::T_GROUP => 'location',

						'size' => 16
					)
				),

				'domain' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Domaine',
						WdElement::T_GROUP => 'location'
					)
				),

				'tld' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'TLD',
						WdElement::T_GROUP => 'location',

						'size' => 8
					)
				),

				'path' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Chemin',
						WdElement::T_GROUP => 'location'
					)
				),

				'language' => new WdElement
				(
					'select', array
					(
						WdElement::T_LABEL => 'Langue',
						WdElement::T_LABEL_POSITION => 'before',
						WdElement::T_REQUIRED => true,
						WdElement::T_GROUP => 'i18n',
						WdElement::T_OPTIONS => array(null => '') + WdI18n::$locale->conventions['languages']
					)
				),

				// http://php.net/manual/fr/timezones.php

				'timezone' => new WdElement
				(
					'select', array
					(
						WdElement::T_LABEL => 'Fuseau horaire',
						WdElement::T_LABEL_POSITION => 'before',
						WdElement::T_GROUP => 'i18n',
						WdElement::T_OPTIONS => array
						(
							'Europe/Paris' => 'Europe/Paris'
						)
					)
				),

				'nativeid' =>  $translation_sources_el,

				'status' => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Status',
						WdElement::T_OPTIONS => array
						(
							0 => 'Le site est hors ligne',
							1 => 'Le site est en ligne',
							2 => 'Le site est en travaux',
							3 => "Le site est interdit d'accès"
						)
					)
				)
			)
		);
	}

	private function get_site_models()
	{
		$models = array();

		$dh = opendir($_SERVER['DOCUMENT_ROOT'] . '/protected');

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