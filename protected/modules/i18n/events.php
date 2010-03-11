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

		if (empty($event->properties['language']) || ($event->properties['language'] != $native))
		{
			$sources = $core->getModule('system.nodes')->model()->select
			(
				array('nid', 'title'), 'WHERE constructor = ? AND language = ? ORDER BY title', array
				(
					(string) $event->module, $native
				)
			)
			->fetchPairs();
		}

		$source_el = null;

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

					WdElement::T_DESCRIPTION => "Il s'agit de l'objet natif, c'est à dire l'objet
					dans la langue du site (ici <strong>" . $native . "</strong>). À noter que les
					objets qui ont une langue neutre ne peuvent pas être traduit, il n'apparaissent
					donc pas dans la liste."
				)
			);
		}
		/*
		else
		{
			$source_el = new WdElement
			(
				'div', array
				(
					WdElement::T_GROUP => 'i18n',
					WdElement::T_INNER_HTML => "Il n'y a pas encore de sources pour la traduction."
				)
			);
		}
		*/

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

							WdElement::T_DESCRIPTION => "Il s'agit de la langue de l'objet. Selon
							la configuration du module, seuls les objets correspondant à la langue
							de la page sont affichés. En général, les objets dont la langue est
							<em>neutre</em> apparaissent quelque soit la langue de la page."
						)
					),

					Node::TNID => $source_el
				)
			)
		);
	}
}