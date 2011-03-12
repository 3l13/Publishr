<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdTitleSlugComboWidget extends WdWidget
{
	const T_NODEID = '#node-id';
	const T_SLUG_NAME = '#slug-name';

	private $title_el;
	private $slug_tease;
	private $slug_el;

	public function __construct($tags=array(), $dummy=null)
	{
		$slugname = isset($tags[self::T_SLUG_NAME]) ? $tags[self::T_SLUG_NAME] : null;
		$label = isset($tags[WdElement::T_LABEL]) ? $tags[WdElement::T_LABEL] : null;
		$label_position = isset($tags[WdElement::T_LABEL_POSITION]) ? $tags[WdElement::T_LABEL_POSITION] : 'before';

		parent::__construct
		(
			'div', $tags + array
			(
				WdElement::T_CHILDREN => array
				(
					$this->title_el = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL_POSITION => $label_position,
							WdElement::T_REQUIRED => true
						)
					),

					$this->slug_tease = new WdElement
					(
						'span', array
						(
							self::T_INNER_HTML => '&nbsp;',

							'class' => 'slug-reminder small'
						)
					),

					'<a href="#slug-collapse" class="small">' . t('fold', array(), array('scope' => array('titleslugcombo', 'element'))) . '</a>',

					'<div class="slug">',

					$this->slug_el = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => '.slug',
							WdElement::T_LABEL_POSITION => 'above',
							WdElement::T_GROUP => 'node',
							WdElement::T_DESCRIPTION => '.slug',

							'name' => $slugname
						)
					),

					'</div>'
				),

				WdElement::T_DATASET => array
				(
					'auto-label' => '<em>' . t('auto', array(), array('scope' => array('titleslugcombo', 'element'))) . '</em>'
				)
			)
		);
	}

	public function set($name, $value=null)
	{
		if ($name == 'name')
		{
			$this->title_el->set('name', $value);

			if (!$this->slug_el->get('name'))
			{
				$this->slug_el->set('name', $value . 'slug');
			}
		}

		parent::set($name, $value);
	}

	public function getInnerHTML()
	{
		global $core, $document;

		$slug = $this->slug_el->get('value');

		$tease = '<strong>Slug&nbsp;:</strong> ';
		$tease .= '<a href="#slug-edit" title="' . t('edit', array(), array('scope' => array('titleslugcombo', 'element'))) . '">' . ($slug ? wd_entities(wd_shorten($slug)) : $this->dataset['auto-label']) . '</a>';
		$tease .= ' <span>&ndash; <a href="slug-delete" class="warn">' . t('reset', array(), array('scope' => array('titleslugcombo', 'element'))) . '</a></span>';

		$this->slug_tease->innerHTML = $tease;

		$rc = parent::getInnerHTML();

		$document->css->add('title-slug-combo.css');
		$document->js->add('title-slug-combo.js');

		$nid = $this->get(self::T_NODEID);

		if ($nid)
		{
			$node = $core->models['system.nodes'][$nid];

			if ($node && $node->url && $node->url[0] != '#')
			{
				$url = $node->url;
				$url_label = wd_shorten($url, 64);

				$rc .= '<p class="small light">';
				$rc .= '<strong>URL&nbsp;:</strong> ' . $url_label;
			}
		}

		return $rc;
	}
}