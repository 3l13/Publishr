<?php

class WdTitleSlugComboElement extends WdElement
{
	const T_SLUG_NAME = '#slug-name';

	protected $title_el;
	protected $slug_tease;
	protected $slug_el;

	public function __construct($tags=array(), $dummy=null)
	{
		$slugname = isset($tags[self::T_SLUG_NAME]) ? $tags[self::T_SLUG_NAME] : null;

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
							WdForm::T_LABEL => 'Titre',
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
							WdElement::T_LABEL_POSITION => 'top',
							WdElement::T_GROUP => 'node',
							WdElement::T_DESCRIPTION => "Le «&nbsp;slug&nbsp;» est la version du titre
							utilisable dans les URL. Il est généralement en minuscules et
							n'est constitué que de lettres, chiffres et traits d'union. S'il est
							vide lors de l'enregistrement, le «&nbsp;slug&nbsp;» sera automatiquement
							crée à partir du titre.",

							'name' => $slugname
						)
					),

					'</div>'
				),

				'class' => 'wd-titleslugcombo'
			)
		);

		global $document;

		$document->addStyleSheet('../public/wdtitleslugcombo.css');
		$document->addJavaScript('../public/wdtitleslugcombo.js');
	}

	public function setTag($name, $value=null)
	{
		if ($name == 'name')
		{
			$this->title_el->setTag('name', $value);

			if (!$this->slug_el->getTag('name'))
			{
				$this->slug_el->setTag('name', $value . 'slug');
			}
		}

		parent::setTag($name, $value);
	}

	public function getInnerHTML()
	{
		$slug = $this->slug_el->getTag('value');

		$tease = '(Slug&nbsp;: ';
		$tease .= '<span class="dark">' . ($slug ? wd_entities($slug) : '<em>non défini</em>') . '</span>';
		$tease .= ' &ndash; <a href="#slug-edit">Éditer</a>)';

		$this->slug_tease->innerHTML = $tease;

		return parent::getInnerHTML();
	}
}