<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WdI18nElement extends WdElement
{
	const T_CONSTRUCTOR = '#i18n-constructor';

	private $el_language;
	private $el_native_nid;

	public function __construct($tags, $dummy=null)
	{
		global $core;

		$languages = $core->models['site.sites']->count('language');

		foreach ($languages as $language => $dummy)
		{
			$languages[$language] = $core->locale->conventions['localeDisplayNames']['languages'][$language];
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
							WdElement::T_LABEL => '.language',
							WdElement::T_LABEL_POSITION => 'before',
							WdElement::T_OPTIONS => array
							(
								null => '.neutral'
							)

							+ $languages,

							WdElement::T_DESCRIPTION => '.language'
						)
					),

					// TODO-20110206: Use the WdI18nLinkElement element

					Node::TNID => $this->el_native_nid = new WdElement
					(
						'em', array
						(
							WdElement::T_LABEL => '.tnid',
							WdElement::T_LABEL_POSITION => 'before',
							WdElement::T_INNER_HTML => "Il n'y a pas d'entrée à traduire.",

							'class' => 'small'
						)
					)
				),

				'class' => 'wd-i18n'
			)
		);
	}

	protected function getInnerHTML()
	{
		global $core, $document;

		$document->js->add('i18n.js');

		$site = $core->site;
		$native = $site->native->language;
		$language = $this->el_language->get('value');
		$sources = null;
		$source_el = null;

		$this->dataset['native'] = $native;

		if (!$language || ($language != $native))
		{
			$constructor = $this->get(self::T_CONSTRUCTOR);

			if ($constructor == 'site.pages')
			{
				$nodes = $core->models['site.pages']->select('nid, parentid, title')->where('language = ?', $native)
				->order('weight, created')->all(PDO::FETCH_OBJ);

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
				$sources = $core->models['system.nodes']->select('nid, title')
				->where('constructor = ? AND language = ?', $constructor, $native)->order('title')
				->pairs;

				foreach ($sources as &$label)
				{
					$label = wd_shorten($label);
				}

				unset($label);
			}
		}

		if ($sources)
		{
			$native_nid = $this->el_native_nid->get('value');

			$this->children[Node::TNID] = new WdElement
			(
				'select', array
				(
					WdElement::T_LABEL => '.tnid',
					WdElement::T_LABEL_POSITION => 'before',
					WdElement::T_GROUP => 'i18n',
					WdElement::T_OPTIONS => array
					(
						null => ''
					)

					+ $sources,

					WdElement::T_DESCRIPTION => t('tnid', array(':native' => $native, ':language' => $site->language), array('scope' => array('element', 'description'))),

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

		return $core->models['system.nodes']->select('language')->where(array('nid' => $nid))->rc;
	}
}