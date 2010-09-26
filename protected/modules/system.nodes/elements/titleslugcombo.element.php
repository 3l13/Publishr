<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdTitleSlugComboElement extends WdElement
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

//		unset($tags[WdElement::T_LABEL]);
//		unset($tags[WdElement::T_LABEL_POSITION]);

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
							//WdElement::T_LABEL => $label,
							WdElement::T_LABEL_POSITION => $label_position,
							WdElement::T_MANDATORY => true
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

					'<a href="#slug-collapse" class="small">Replier</a>',

					'<div class="slug">',

					$this->slug_el = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => 'Slug',
							WdElement::T_LABEL_POSITION => 'before',
							WdElement::T_GROUP => 'node',
							WdElement::T_DESCRIPTION => "Le «&nbsp;slug&nbsp;» est la version du
							titre utilisable dans les URL. Il est généralement en minuscules et
							n'est constitué que de lettres, chiffres et traits d'union. S'il est
							vide lors de l'enregistrement, le «&nbsp;slug&nbsp;» sera
							automatiquement crée à partir du titre.",

							'name' => $slugname
						)
					),

					'</div>'
				),

				WdElement::T_JS_OPTIONS => array
				(
					'autoLabel' => '<em>auto</em>'
				),

				'class' => 'wd-titleslugcombo'
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
		$slug = $this->slug_el->get('value');
		$options = $this->get(self::T_JS_OPTIONS);

		$tease = '<strong>Slug&nbsp;:</strong> ';
		$tease .= '<a href="#slug-edit" title="Cliquer pour éditer">' . ($slug ? wd_entities(wd_shorten($slug)) : $options['autoLabel']) . '</a>';
		$tease .= ' <span>&ndash; <a href="slug-delete" class="warn">Mettre à zéro</a></span>';

		$this->slug_tease->innerHTML = $tease;

		$rc = parent::getInnerHTML();

		global $document;

		$document->css->add('titleslugcombo.css');
		$document->js->add('titleslugcombo.js');

		$nid = $this->get(self::T_NODEID);

		if ($nid)
		{
			global $core;

			$entry = $core->models['system.nodes']->load($nid);

			if ($entry && $entry->url && $entry->url[0] != '#')
			{
				$url = $entry->url;
				$url_label = wd_shorten($url, 64);

				$rc .= '<p class="small light">';
				$rc .= '<strong>URL&nbsp;:</strong> ' . $url_label;
				$rc .= ' &ndash; <a href="' . $url . '" class="view">Voir sur le site</a></p>';
			}
		}

		return $rc;
	}
}