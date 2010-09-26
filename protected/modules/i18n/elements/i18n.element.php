<?php

class WdI18nElement extends WdElement
{
	const T_CONSTRUCTOR = '#i18n-constructor';

	private $el_language;
	private $el_native_nid;

	public function __construct($tags, $dummy=null)
	{
		global $app;

		$languages = array();

		foreach (WdLocale::$languages as $language)
		{
			$languages[$language] = t($language, array(), array('scope' => 'i18n.languages'));
		}

		parent::__construct
		(
			'div', $tags + array
			(
				WdElement::T_CHILDREN => array
				(
					Node::LANGUAGE => $this->el_language = new WdElement
					(
						'select', array
						(
							WdElement::T_LABEL => 'Langue',
							WdElement::T_LABEL_POSITION => 'before',
							WdElement::T_OPTIONS => array
							(
								null => '<neutre>'
							)

							+ $languages,

//							WdElement::T_DEFAULT => $app->working_site->language,

							WdElement::T_DESCRIPTION => "Il s'agit de la langue de l'entrée. En
							général, seules les entrées qui ont la même langue que la page, ou une
							langue neutre, apparaissent sur la page."
						)
					),

					Node::TNID => $this->el_native_nid = new WdElement
					(
						'em', array
						(
							WdElement::T_LABEL => 'Source de la traduction',
							WdElement::T_LABEL_POSITION => 'before',
							WdElement::T_INNER_HTML => "Il n'y a pas d'entrée à traduire.",

							'class' => 'small'
						)
					)
				),

				WdElement::T_JS_OPTIONS => array
				(
					'native' => WdLocale::$native
				),

				'class' => 'wd-i18n'
			)
		);
	}

	protected function getInnerHTML()
	{
		global $core, $document;

		$document->js->add('i18n.js');


		$language = $this->el_language->get('value');
		$native_nid = $this->el_native_nid->get('value');

		#
		# sources for the translation
		#

		$native = WdLocale::$native;

		$sources = null;
		$source_el = null;

		if (!$language || ($language != $native))
		{
			$constructor = $this->get(self::T_CONSTRUCTOR);

			if ($constructor == 'site.pages')
			{
				$nodes = $core->models['site.pages']->select
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
				$sources = $core->models['system.nodes']->select
				(
					array('nid', 'title'), 'WHERE constructor = ? AND language = ? ORDER BY title', array
					(
						$constructor, $native
					)
				)
				->fetchPairs();

				foreach ($sources as &$label)
				{
					$label = wd_shorten($label);
				}

				unset($label);
			}
		}

		if ($sources)
		{
			$this->children[Node::TNID] = new WdElement
			(
				'select', array
				(
					WdElement::T_LABEL => 'Source de la traduction',
					WdElement::T_LABEL_POSITION => 'before',
					WdElement::T_GROUP => 'i18n',
					WdElement::T_OPTIONS => array
					(
						null => ''
					)

					+ $sources,

					WdElement::T_DESCRIPTION => "Il s'agit de l'objet dans la langue native du site
					(ici <strong>" . $native . "</strong>). Les objets qui ont une langue neutre ne
					peuvent pas être traduit, il n'apparaissent donc pas dans la liste.",

					'name' => Node::TNID,
					'value' => $native_nid
				)
			);
		}

		return parent::getInnerHTML();
	}

	static public function operation_nodes_language(WdOperation $operation)
	{
		global $core;

		$nid = $operation->params['nid'];

		return $core->models['system.nodes']->select('language', 'WHERE nid = ?', array($nid))->fetchColumnAndClose();
	}
}