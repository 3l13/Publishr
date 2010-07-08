<?php

class i18n_WdEvents
{
	static public function alter_block_edit(WdEvent $event)
	{
		if (!($event->module instanceof system_nodes_WdModule))
		{
			return;
		}

		if (count(WdLocale::$languages) < 2)
		{
			return;
		}

		global $core;

		#
		# sources for the translation
		#

		$native = WdLocale::$native;

		$sources = null;
		$source_el = null;

		if (empty($event->properties['language']) || ($event->properties['language'] != $native))
		{
			$constructor = (string) $event->module;

			if ($constructor == 'site.pages')
			{
				$model = $core->models['site.pages'];

				$nodes = $model->select
				(
					array('nid', 'parentid', 'title'), 'WHERE language = ? ORDER BY weight, created', array
					(
						$native
					)
				)
				->fetchAll(PDO::FETCH_OBJ);

				$tree = site_pages_WdModel::nestNodes($nodes);

				if ($tree)
				{
					site_pages_WdModel::setNodesDepth($tree);
					$entries = site_pages_WdModel::levelNodesById($tree);

					foreach ($entries as $entry)
					{
						$sources[$entry->nid] = str_repeat("\xC2\xA0", $entry->depth * 4) . $entry->title;
					}
				}
			}
			else
			{
				$sources = $core->getModule('system.nodes')->model()->select
				(
					array('nid', 'title'), 'WHERE constructor = ? AND language = ? ORDER BY title', array
					(
						$constructor, $native
					)
				)
				->fetchPairs();
			}
		}

		if ($sources)
		{
			$source_el = new WdElement
			(
				'select', array
				(
					WdForm::T_LABEL => 'Source de la traduction',
					WdElement::T_GROUP => 'i18n',
					WdElement::T_OPTIONS => array
					(
						null => ''
					)

					+ $sources,

					WdElement::T_DESCRIPTION => "Il s'agit de l'objet dans la langue native du site
					(ici <strong>" . $native . "</strong>). Les objets qui ont une langue neutre ne
					peuvent pas être traduit, il n'apparaissent donc pas dans la liste."
				)
			);
		}
		else
		{
			$source_el = new WdElement
			(
				'div', array
				(
					WdElement::T_GROUP => 'i18n',
					WdElement::T_INNER_HTML => "Il n'y a pas de sources pour la traduction."
				)
			);
		}

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'i18n' => array
					(
						'title' => 'Internationalisation',
						'weight' => 100
					)
				),

				WdElement::T_CHILDREN => array
				(
					Node::LANGUAGE => new WdElement
					(
						'select', array
						(
							WdForm::T_LABEL => 'Langue',
							WdElement::T_GROUP => 'i18n',
							WdElement::T_OPTIONS => array
							(
								null => 'Langue neutre',
								'en' => 'Anglais',
								'fr' => 'Français'
							),

							WdElement::T_DESCRIPTION => "Il s'agit de la langue de l'objet. En
							général, seuls les objets qui ont la même langue que la page, ou qui
							ont une langue neutre, apparaissent sur la page."
						)
					),

					Node::TNID => $source_el
				)
			)
		);
	}
}